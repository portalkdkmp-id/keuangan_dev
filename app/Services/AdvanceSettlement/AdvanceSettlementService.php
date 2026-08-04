<?php

namespace App\Services\AdvanceSettlement;

use App\Enums\AccountabilityStatus;
use App\Enums\AdvanceStatus;
use App\Models\AdvanceDetail;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\AccountabilityReportSubmittedNotification;
use App\Services\Accountability\FundAccountabilityCalculator;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdvanceSettlementService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly FundAccountabilityCalculator $calculator,
        private readonly AuditLogService $audit,
    ) {}

    public function saveDraft(User $actor, AdvanceDetail $advance, array $data, array $purchaseProofs, array $paymentProofs): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $advance, $data, $purchaseProofs, $paymentProofs) {
            $locked = AdvanceDetail::whereKey($advance->id)->lockForUpdate()->firstOrFail();
            $this->ensureOwnerCanSettle($actor, $locked);

            $report = $locked->settlement;
            if ($report && ! in_array($report->status, [AccountabilityStatus::DRAFT, AccountabilityStatus::REVISION_REQUESTED], true)) {
                throw ValidationException::withMessages(['settlement' => 'Settlement tidak dapat diubah pada status saat ini.']);
            }

            $totals = $this->calculator->calculate((float) $locked->disbursed_amount, $data['items']);
            $values = [
                'financial_submission_id' => $locked->financial_submission_id,
                'source_type' => 'advance',
                'advance_detail_id' => $locked->id,
                'submitted_by' => $actor->id,
                'status' => AccountabilityStatus::DRAFT,
                'received_amount' => $locked->disbursed_amount,
                ...$totals,
                'summary' => $data['summary'],
                'usage_date_from' => $data['usage_date_from'] ?? null,
                'usage_date_to' => $data['usage_date_to'] ?? null,
            ];

            if ($report) {
                $report->update($values);
            } else {
                $report = FundAccountabilityReport::create([
                    ...$values,
                    'report_number' => $this->numbers->generateAccountabilityNumber(),
                ]);
            }

            $this->syncItems($actor, $report, $data['items'], $purchaseProofs, $paymentProofs);
            $locked->update(['advance_status' => AdvanceStatus::SETTLEMENT_DRAFT]);
            $this->audit->record('advance.settlement_saved', 'Draft settlement uang panjar disimpan.', $report, [], $totals);

            return $report->refresh()->load(['items.attachments', 'advanceDetail']);
        });
    }

    public function submit(User $actor, FundAccountabilityReport $report): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report) {
            $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->source_type !== 'advance' || $locked->submitted_by !== $actor->id || ! in_array($locked->status, [AccountabilityStatus::DRAFT, AccountabilityStatus::REVISION_REQUESTED], true)) {
                throw ValidationException::withMessages(['settlement' => 'Settlement tidak dapat diajukan.']);
            }

            foreach ($locked->items()->with('attachments')->get() as $item) {
                if (! $item->attachments->contains('attachment_type', 'purchase_proof') || ! $item->attachments->contains('attachment_type', 'payment_proof')) {
                    throw ValidationException::withMessages(['attachments' => 'Setiap item wajib memiliki bukti pembelian dan bukti pembayaran.']);
                }
            }

            $locked->update(['status' => AccountabilityStatus::SUBMITTED, 'submitted_at' => now()]);
            $locked->advanceDetail()->update(['advance_status' => AdvanceStatus::SETTLEMENT_SUBMITTED]);
            $this->audit->record('advance.settlement_submitted', 'Settlement uang panjar dikirim ke Finance Staff.', $locked);
            DB::afterCommit(fn () => User::role('finance_staff')->where('is_active', true)->whereKeyNot($actor->id)->get()->each(
                fn (User $user) => $user->notify(new AccountabilityReportSubmittedNotification($locked)),
            ));

            return $locked;
        });
    }

    private function ensureOwnerCanSettle(User $actor, AdvanceDetail $advance): void
    {
        if ($advance->responsible_user_id !== $actor->id || ! in_array($advance->advance_status, [AdvanceStatus::SETTLEMENT_DUE, AdvanceStatus::SETTLEMENT_DRAFT, AdvanceStatus::SETTLEMENT_REVISION_REQUESTED], true)) {
            throw ValidationException::withMessages(['settlement' => 'Uang panjar belum dapat dipertanggungjawabkan oleh user ini.']);
        }
    }

    private function syncItems(User $actor, FundAccountabilityReport $report, array $items, array $purchaseProofs, array $paymentProofs): void
    {
        $existing = $report->items()->with('attachments')->get()->values();
        $types = SubmissionRequestType::whereIn('id', collect($items)->pluck('category_id')->filter())->pluck('name', 'id');
        $report->items()->delete();

        foreach ($items as $index => $row) {
            $item = $report->items()->create([
                ...$row,
                'category_name_snapshot' => isset($row['category_id']) ? $types[$row['category_id']] ?? null : null,
            ]);
            foreach (['purchase_proof' => $purchaseProofs[$index] ?? [], 'payment_proof' => $paymentProofs[$index] ?? []] as $type => $files) {
                $old = $existing->get($index)?->attachments->where('attachment_type', $type) ?? collect();
                if (count($files) === 0) {
                    $old->each(fn ($attachment) => $item->attachments()->create($attachment->only([
                        'fund_accountability_report_id', 'uploaded_by', 'original_name', 'stored_name', 'disk', 'path', 'mime_type', 'extension', 'size', 'attachment_type',
                    ])));
                } else {
                    $old->each(fn ($attachment) => Storage::disk($attachment->disk)->delete($attachment->path));
                    foreach ($files as $file) {
                        $this->storeProof($actor, $report, $item->id, $file, $type);
                    }
                }
            }
        }
    }

    private function storeProof(User $actor, FundAccountabilityReport $report, string $itemId, UploadedFile $file, string $type): void
    {
        $path = $file->store("advance-settlements/{$report->id}/{$itemId}", 'local');
        $report->attachments()->create([
            'fund_accountability_item_id' => $itemId,
            'uploaded_by' => $actor->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize() ?: 0,
            'attachment_type' => $type,
        ]);
    }
}
