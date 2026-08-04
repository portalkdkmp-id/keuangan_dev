<?php

namespace App\Services\Accountability;

use App\Enums\AccountabilityStatus;
use App\Enums\DistributionStatus;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\AccountabilityReportApprovedNotification;
use App\Notifications\AccountabilityReportSubmittedNotification;
use App\Notifications\AccountabilityReportVerifiedNotification;
use App\Notifications\AccountabilityRevisionRequestedNotification;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundAccountabilityService
{
    public function __construct(private readonly DocumentNumberService $numbers, private readonly FundAccountabilityCalculator $calculator, private readonly AuditLogService $audit, private readonly AccountabilityClosingService $closing) {}

    public function create(User $actor, FinancialSubmission $submission, array $data, array $files): FundAccountabilityReport
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($actor, $submission, $data, $files, &$storedFiles) {
                $locked = FinancialSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
                if ($locked->submitted_by !== $actor->id || $locked->accountabilityReport()->exists()) {
                    throw ValidationException::withMessages(['report' => 'Laporan tidak dapat dibuat atau sudah tersedia.']);
                }
                $received = $locked->receiptConfirmations()->sum('amount');
                if ((float) $received <= 0) {
                    throw ValidationException::withMessages(['report' => 'Dana belum dikonfirmasi diterima.']);
                }
                $totals = $this->calculator->calculate($received, $data['items']);
                $report = FundAccountabilityReport::create(['financial_submission_id' => $locked->id, 'submitted_by' => $actor->id, 'report_number' => $this->numbers->generateAccountabilityNumber(), 'status' => AccountabilityStatus::DRAFT, 'received_amount' => $received, ...$totals, 'summary' => $data['summary'], 'usage_date_from' => $data['usage_date_from'] ?? null, 'usage_date_to' => $data['usage_date_to'] ?? null]);
                $this->syncItems($report, $data['items']);
                $this->storeFiles($report, $actor, $files, $data['attachment_type'] ?? 'receipt', $storedFiles);
                $this->audit->record('accountability.draft_created', 'Draft pertanggungjawaban dibuat.', $report, [], ['received_amount' => $received, ...$totals]);

                return $report->refresh();
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function update(User $actor, FundAccountabilityReport $report, array $data, array $files): FundAccountabilityReport
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($actor, $report, $data, $files, &$storedFiles) {
                $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
                $this->ensureEditable($locked, $actor);
                $totals = $this->calculator->calculate($locked->received_amount, $data['items']);
                $locked->update([...$totals, 'summary' => $data['summary'], 'usage_date_from' => $data['usage_date_from'] ?? null, 'usage_date_to' => $data['usage_date_to'] ?? null, 'status' => AccountabilityStatus::DRAFT]);
                $this->syncItems($locked, $data['items']);
                $this->storeFiles($locked, $actor, $files, $data['attachment_type'] ?? 'receipt', $storedFiles);
                $this->audit->record('accountability.updated', 'Pertanggungjawaban diperbarui.', $locked, [], $totals);

                return $locked->refresh();
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function submit(User $actor, FundAccountabilityReport $report): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report) {
            $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $this->ensureEditable($locked, $actor);
            if (! $locked->items()->exists() || ! $locked->attachments()->exists()) {
                throw ValidationException::withMessages(['report' => 'Minimal satu item dan satu bukti wajib tersedia.']);
            }
            $locked->update(['status' => AccountabilityStatus::SUBMITTED, 'submitted_at' => now()]);
            $locked->submission->disbursement?->update(['distribution_status' => DistributionStatus::ACCOUNTABILITY_SUBMITTED]);
            $this->audit->record('accountability.submitted', 'Pertanggungjawaban dikirim.', $locked);
            DB::afterCommit(fn () => User::role('finance_staff')->where('is_active', true)->get()->each(fn (User $u) => $u->notify(new AccountabilityReportSubmittedNotification($locked))));

            return $locked;
        });
    }

    public function startReview(User $actor, FundAccountabilityReport $report): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report) {
            $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== AccountabilityStatus::SUBMITTED) {
                throw ValidationException::withMessages(['status' => 'Laporan tidak menunggu review.']);
            }
            if ($locked->source_type === 'advance' && $locked->submitted_by === $actor->id) {
                throw ValidationException::withMessages(['reviewer' => 'Penanggung jawab panjar tidak dapat mereview settlement miliknya sendiri.']);
            }
            $locked->update(['status' => AccountabilityStatus::FINANCE_REVIEW, 'finance_reviewed_by' => $actor->id, 'finance_reviewed_at' => now()]);
            $locked->submission->disbursement?->update(['distribution_status' => DistributionStatus::UNDER_VERIFICATION]);

            return $locked;
        });
    }

    public function requestRevision(User $actor, FundAccountabilityReport $report, string $notes): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report, $notes) {
            $locked = $this->lockFinanceReview($report, $actor);
            $locked->update(['status' => AccountabilityStatus::REVISION_REQUESTED, 'finance_notes' => $notes]);
            $this->closing->syncAdvanceStatus($locked);
            $this->audit->record('accountability.revision_requested', 'Revisi pertanggungjawaban diminta.', $locked, [], ['notes' => $notes]);
            DB::afterCommit(fn () => $locked->submitter->notify(new AccountabilityRevisionRequestedNotification($locked)));

            return $locked;
        });
    }

    public function verify(User $actor, FundAccountabilityReport $report, ?string $notes): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report, $notes) {
            $locked = $this->lockFinanceReview($report, $actor);
            $locked->update(['status' => AccountabilityStatus::FINANCE_VERIFIED, 'finance_notes' => $notes, 'finance_reviewed_at' => now()]);
            $this->closing->syncAdvanceStatus($locked);
            $this->audit->record('accountability.finance_verified', 'Pertanggungjawaban diverifikasi.', $locked);
            DB::afterCommit(fn () => User::role('finance_approver')->where('is_active', true)->get()->each(fn (User $u) => $u->notify(new AccountabilityReportVerifiedNotification($locked))));

            return $locked;
        });
    }

    public function approve(User $actor, FundAccountabilityReport $report, ?string $notes): FundAccountabilityReport
    {
        return DB::transaction(function () use ($actor, $report, $notes) {
            $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== AccountabilityStatus::FINANCE_VERIFIED) {
                throw ValidationException::withMessages(['status' => 'Laporan belum terverifikasi.']);
            }
            $locked->update(['status' => AccountabilityStatus::APPROVED, 'approved_by' => $actor->id, 'approved_at' => now(), 'approval_notes' => $notes]);
            $this->closing->settleAfterApproval($locked);
            if ($locked->fresh()->status === AccountabilityStatus::CLOSED) {
                $locked->submission->disbursement?->update(['distribution_status' => DistributionStatus::CLOSED]);
            }
            $this->audit->record('accountability.approved', 'Pertanggungjawaban disetujui.', $locked, [], ['settlement_status' => $locked->fresh()->status->value]);
            DB::afterCommit(fn () => $locked->submitter->notify(new AccountabilityReportApprovedNotification($locked)));

            return $locked;
        });
    }

    private function ensureEditable(FundAccountabilityReport $report, User $actor): void
    {
        if ($report->submitted_by !== $actor->id || ! in_array($report->status, [AccountabilityStatus::DRAFT, AccountabilityStatus::REVISION_REQUESTED], true)) {
            throw ValidationException::withMessages(['status' => 'Laporan tidak dapat diedit.']);
        }
    }

    private function lockFinanceReview(FundAccountabilityReport $report, User $actor): FundAccountabilityReport
    {
        $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
        if ($locked->status !== AccountabilityStatus::FINANCE_REVIEW || $locked->finance_reviewed_by !== $actor->id) {
            throw ValidationException::withMessages(['status' => 'Laporan tidak sedang direview oleh Anda.']);
        }

        return $locked;
    }

    private function syncItems(FundAccountabilityReport $report, array $items): void
    {
        $types = SubmissionRequestType::whereIn('id', collect($items)->pluck('category_id')->filter())->pluck('name', 'id');
        $report->items()->delete();
        foreach ($items as $item) {
            $report->items()->create([...$item, 'category_name_snapshot' => isset($item['category_id']) ? $types[$item['category_id']] ?? null : null]);
        }
    }

    /** @param array<int, UploadedFile> $files */
    private function storeFiles(FundAccountabilityReport $report, User $actor, array $files, string $type, array &$storedFiles): void
    {
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $name = Str::uuid().'.'.$extension;
            $path = "fund-accountability/{$report->id}/{$name}";
            $disk = config('finance.attachment_disk', 'local');
            Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
            $storedFiles[] = [$disk, $path];
            $report->attachments()->create(['uploaded_by' => $actor->id, 'original_name' => basename($file->getClientOriginalName()), 'stored_name' => $name, 'disk' => $disk, 'path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'extension' => $extension, 'size' => $file->getSize() ?: 0, 'attachment_type' => $type]);
        }
    }

    private function deleteStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }
    }
}
