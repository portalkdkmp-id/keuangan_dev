<?php

namespace App\Services\FundReturn;

use App\Enums\AccountabilityStatus;
use App\Enums\FundReturnAttachmentType;
use App\Enums\FundReturnStatus;
use App\Models\CompanyBankAccount;
use App\Models\FundAccountabilityReport;
use App\Models\FundReturn;
use App\Models\User;
use App\Notifications\FundReturnWorkflowNotification;
use App\Services\Accountability\AccountabilityClosingService;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FundReturnService
{
    public function __construct(private DocumentNumberService $numbers, private AccountabilityClosingService $closing, private AuditLogService $audit) {}

    public function createDraft(User $actor, FundAccountabilityReport $report, array $data, ?UploadedFile $proof): FundReturn
    {
        return DB::transaction(function () use ($actor, $report, $data, $proof) {
            $locked = FundAccountabilityReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->submitted_by !== $actor->id || $locked->status !== AccountabilityStatus::RETURN_PENDING || (float) $locked->remaining_amount <= 0) {
                throw ValidationException::withMessages(['report' => 'Laporan tidak dapat dibuatkan pengembalian dana.']);
            }if ($locked->fundReturn()->exists()) {
                throw ValidationException::withMessages(['report' => 'Pengembalian dana sudah dibuat.']);
            }$return = $this->persist($actor, $locked, null, $data, $proof);
            $this->audit->record('fund_return.draft_created', 'Draft pengembalian dana dibuat.', $return, [], ['expected_amount' => $return->expected_amount]);

            return $return;
        });
    }

    public function updateDraft(User $actor, FundReturn $return, array $data, ?UploadedFile $proof): FundReturn
    {
        return DB::transaction(function () use ($actor, $return, $data, $proof) {
            $locked = FundReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            if ($locked->returned_by !== $actor->id || ! in_array($locked->status, [FundReturnStatus::DRAFT, FundReturnStatus::REVISION_REQUESTED], true)) {
                throw ValidationException::withMessages(['fund_return' => 'Pengembalian tidak dapat diubah.']);
            }

            return $this->persist($actor, $locked->accountabilityReport, $locked, $data, $proof);
        });
    }

    public function submit(User $actor, FundReturn $return): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::DRAFT, FundReturnStatus::REVISION_REQUESTED], FundReturnStatus::SUBMITTED, 'fund_return.submitted', function ($locked) {
            $required = $locked->payment_method === 'cash' ? 'handover_receipt' : 'transfer_proof';
            if (! $locked->attachments()->where('attachment_type', $required)->exists()) {
                throw ValidationException::withMessages(['proof' => 'Bukti pengembalian wajib diunggah.']);
            }$locked->submitted_at = now();
        });
    }

    public function startReview(User $actor, FundReturn $return): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::SUBMITTED], FundReturnStatus::FINANCE_REVIEW, 'fund_return.review_started');
    }

    public function requestRevision(User $actor, FundReturn $return, string $notes): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::FINANCE_REVIEW], FundReturnStatus::REVISION_REQUESTED, 'fund_return.revision_requested', fn ($r) => $r->revision_notes = $notes);
    }

    public function verify(User $actor, FundReturn $return, ?string $notes): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::FINANCE_REVIEW], FundReturnStatus::FINANCE_VERIFIED, 'fund_return.verified', function ($r) use ($actor, $notes) {
            $r->verified_by = $actor->id;
            $r->verified_at = now();
            $r->verification_notes = $notes;
        });
    }

    public function approve(User $actor, FundReturn $return, ?string $notes): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::FINANCE_VERIFIED], FundReturnStatus::CLOSED, 'fund_return.approved', function ($r) use ($actor, $notes) {
            if ((string) $r->returned_amount !== (string) $r->expected_amount) {
                throw ValidationException::withMessages(['amount' => 'Nominal pengembalian tidak sesuai.']);
            }$r->approved_by = $actor->id;
            $r->approved_at = now();
            $r->closed_at = now();
            $r->approval_notes = $notes;
            $this->closing->close($r->accountabilityReport);
        });
    }

    public function reject(User $actor, FundReturn $return, string $notes): FundReturn
    {
        return $this->transition($actor, $return, [FundReturnStatus::FINANCE_REVIEW, FundReturnStatus::FINANCE_VERIFIED], FundReturnStatus::REJECTED, 'fund_return.rejected', function ($locked) use ($notes) {
            $locked->approval_notes = $notes;
            $locked->rejected_at = now();
        });
    }

    private function persist(User $actor, FundAccountabilityReport $report, ?FundReturn $return, array $data, ?UploadedFile $proof): FundReturn
    {
        $destination = CompanyBankAccount::where('is_active', true)->findOrFail($data['destination_company_bank_account_id']);
        $source = null;
        if ($data['source_user_bank_account_id'] ?? null) {
            $source = $actor->bankAccounts()->where('is_active', true)->findOrFail($data['source_user_bank_account_id']);
        }if ($data['payment_method'] === 'bank_transfer' && ! $source) {
            throw ValidationException::withMessages(['source_user_bank_account_id' => 'Rekening sumber wajib dipilih.']);
        }if (in_array($data['payment_method'], ['cash', 'other']) && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages(['notes' => 'Catatan wajib untuk metode nontransfer.']);
        }$values = ['financial_submission_id' => $report->financial_submission_id, 'fund_accountability_report_id' => $report->id, 'return_number' => $return?->return_number ?? $this->numbers->generateFundReturnNumber(), 'returned_by' => $actor->id, 'source_user_bank_account_id' => $source?->id, 'source_bank_name_snapshot' => $source?->bank_name, 'source_account_number_snapshot' => $source?->account_number, 'source_account_holder_snapshot' => $source?->account_holder_name, 'destination_company_bank_account_id' => $destination->id, 'destination_bank_name_snapshot' => $destination->bank_name, 'destination_account_number_snapshot' => $destination->account_number, 'destination_account_holder_snapshot' => $destination->account_holder_name, 'expected_amount' => $report->remaining_amount, 'returned_amount' => $report->remaining_amount, 'transfer_date' => $data['transfer_date'], 'transferred_at' => $data['transferred_at'], 'payment_method' => $data['payment_method'], 'transaction_reference' => $data['transaction_reference'] ?? null, 'notes' => $data['notes'] ?? null, 'status' => $return?->status ?? FundReturnStatus::DRAFT];
        $return ? $return->update($values) : $return = FundReturn::create($values);
        if ($proof) {
            $kind = $data['payment_method'] === 'cash' ? FundReturnAttachmentType::HANDOVER_RECEIPT : FundReturnAttachmentType::TRANSFER_PROOF;
            $path = $proof->store('fund-returns/'.$return->id, 'local');
            $return->attachments()->create(['uploaded_by' => $actor->id, 'attachment_type' => $kind, 'original_name' => $proof->getClientOriginalName(), 'stored_name' => basename($path), 'disk' => 'local', 'path' => $path, 'mime_type' => $proof->getMimeType() ?: 'application/octet-stream', 'extension' => $proof->getClientOriginalExtension(), 'size' => $proof->getSize()]);
        }

        return $return->refresh();
    }

    private function transition(User $actor, FundReturn $return, array $from, FundReturnStatus $to, string $event, ?callable $mutate = null): FundReturn
    {
        return DB::transaction(function () use ($return, $from, $to, $event, $mutate) {
            $locked = FundReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['status' => 'Transisi pengembalian dana tidak diizinkan.']);
            }$mutate?->call($this, $locked);
            $locked->status = $to;
            $locked->save();
            $this->audit->record($event, 'Status pengembalian dana berubah.', $locked, [], ['status' => $to->value]);
            DB::afterCommit(function () use ($locked, $to) {
                $recipients = match ($to) {
                    FundReturnStatus::SUBMITTED => User::role('finance_staff')->where('is_active', true)->get(),
                    FundReturnStatus::FINANCE_VERIFIED => User::role('finance_approver')->where('is_active', true)->get(),
                    FundReturnStatus::REVISION_REQUESTED => collect([$locked->returner]),
                    FundReturnStatus::CLOSED => User::role(['finance_staff', 'finance_director'])->where('is_active', true)->get()->push($locked->returner),
                    default => collect(),
                };
                $url = $to === FundReturnStatus::FINANCE_VERIFIED ? "/approval/fund-returns/{$locked->id}" : ($to === FundReturnStatus::SUBMITTED ? "/finance/fund-returns/{$locked->id}" : "/fund-returns/{$locked->id}");
                $recipients->filter()->unique('id')->each(fn (User $user) => $user->notify(new FundReturnWorkflowNotification($locked, 'Status Pengembalian Dana', $url)));
            });

            return $locked->refresh();
        });
    }
}
