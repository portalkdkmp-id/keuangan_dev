<?php

namespace App\Services\FinanceSubmission;

use App\Enums\ApprovalReviewStatus;
use App\Enums\RevisionRequestStatus;
use App\Enums\SubmissionStatus;
use App\Models\FinanceSubmissionDetail;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRevisionRequest;
use App\Models\User;
use App\Notifications\SubmissionForwardedToApprovalNotification;
use App\Notifications\SubmissionRevisionRequestedNotification;
use App\Services\Audit\AuditLogService;
use App\Services\Submission\SubmissionItemService;
use App\Services\Submission\SubmissionStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceSubmissionService
{
    public function __construct(
        private readonly SubmissionStatusService $statuses,
        private readonly SubmissionItemService $items,
        private readonly AuditLogService $auditLog,
    ) {}

    public function updateFinanceDetail(User $user, FinancialSubmission $submission, array $data): FinanceSubmissionDetail
    {
        return DB::transaction(function () use ($user, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::FINANCE_REVIEW, 'Detail keuangan hanya bisa diperbarui saat finance review.');
            $locked->update([
                'title' => $data['title'],
                'submission_request_category_id' => $data['submission_request_category_id'],
                'submission_request_type_id' => $data['submission_request_type_id'],
                'needed_date' => $data['needed_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_urgent' => $data['is_urgent'] ?? $locked->is_urgent,
            ]);
            if (isset($data['items'])) {
                $items = collect($data['items'])->map(fn (array $item) => ['request_type_id' => $item['request_type_id'], 'other_type_name' => $item['other_type_name'] ?? null, 'description' => $item['name'], 'quantity' => 1, 'unit' => 'item', 'unit_price' => $item['amount'], 'notes' => $data['notes'] ?? null])->all();
                $total = $this->items->replaceItems($locked, $items);
                $locked->update(['submission_request_type_id' => $data['items'][0]['request_type_id'], 'total_amount' => $total]);
                $data['amount'] = $total;
            }
            $detail = FinanceSubmissionDetail::updateOrCreate(
                ['financial_submission_id' => $locked->id],
                [
                    'finance_notes' => $data['finance_notes'] ?? null,
                    'validated_total_amount' => $data['amount'],
                    'staff_reviewed_at' => now(),
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'created_by' => $locked->financeDetail?->created_by ?? $user->id,
                    'updated_by' => $user->id,
                ]
            );

            $this->auditLog->record('finance_detail.updated', 'Detail keuangan diperbarui.', $locked, [], [
                'submission_id' => $locked->id,
                'submission_number' => $locked->submission_number,
                'validated_total_amount' => $detail->validated_total_amount,
            ]);

            return $detail;
        });
    }

    public function requestRevision(User $user, FinancialSubmission $submission, array $data): SubmissionRevisionRequest
    {
        return DB::transaction(function () use ($user, $submission, $data) {
            $locked = FinancialSubmission::query()->with('submitter')->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::FINANCE_REVIEW, 'Revisi hanya dapat diminta saat finance review.');
            if ($locked->openRevisionRequest()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['revision' => 'Masih ada permintaan revisi aktif.']);
            }

            $revisionNumber = $locked->revision_count + 1;
            $revision = $locked->revisionRequests()->create([
                'requested_by' => $user->id,
                'revision_number' => $revisionNumber,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'fields' => $data['fields'],
                'status' => RevisionRequestStatus::OPEN,
                'requested_at' => now(),
            ]);

            $locked->forceFill([
                'revision_count' => $revisionNumber,
                'last_revision_requested_at' => now(),
            ])->save();

            $this->statuses->transition($locked, SubmissionStatus::REVISION_REQUESTED, $user, 'revision_requested', $data['message'], [
                'revision_number' => $revisionNumber,
                'fields' => $data['fields'],
            ]);

            DB::afterCommit(fn () => $locked->submitter?->notify(new SubmissionRevisionRequestedNotification($locked->fresh(), $revision, $user)));

            return $revision;
        });
    }

    public function validateSubmission(User $user, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission) {
            $locked = FinancialSubmission::query()->with(['items', 'financeDetail'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::FINANCE_REVIEW, 'Pengajuan tidak sedang direview.');
            if (! $locked->financeDetail?->validated_total_amount || (float) $locked->financeDetail->validated_total_amount <= 0) {
                throw ValidationException::withMessages(['finance_detail' => 'Detail keuangan dan nominal validasi wajib diisi.']);
            }
            if ($locked->openRevisionRequest()->exists()) {
                throw ValidationException::withMessages(['revision' => 'Masih ada permintaan revisi aktif.']);
            }
            if ($locked->isAdvance()) {
                if ($locked->submitted_by === $user->id) {
                    throw ValidationException::withMessages(['advance' => 'Pembuat panjar tidak dapat memvalidasi pengajuannya sendiri.']);
                }
                if ((float) $locked->financeDetail->validated_total_amount > (float) $locked->advanceDetail->estimated_amount) {
                    throw ValidationException::withMessages(['validated_total_amount' => 'Nominal validasi tidak boleh melebihi estimasi panjar.']);
                }
                if ((float) $locked->financeDetail->validated_total_amount < (float) $locked->advanceDetail->estimated_amount && blank($locked->financeDetail->finance_notes)) {
                    throw ValidationException::withMessages(['finance_notes' => 'Catatan wajib jika nominal panjar dikurangi.']);
                }
            }
            if ($locked->isReimbursement()) {
                $detail = $locked->reimbursementDetail;
                if ((float) $locked->financeDetail->validated_total_amount > (float) $detail->claimed_amount) {
                    throw ValidationException::withMessages(['validated_total_amount' => 'Nominal validasi tidak boleh melebihi total klaim reimbursement.']);
                }
                if ((float) $locked->financeDetail->validated_total_amount < (float) $detail->claimed_amount && blank($locked->financeDetail->finance_notes)) {
                    throw ValidationException::withMessages(['finance_notes' => 'Catatan wajib jika nominal validasi lebih kecil dari klaim.']);
                }
                $detail->update(['finance_validated_amount' => $locked->financeDetail->validated_total_amount, 'finance_notes' => $locked->financeDetail->finance_notes]);
            }

            $locked->forceFill(['finance_validated_by' => $user->id, 'finance_validated_at' => now()])->save();

            return $this->statuses->transition($locked, SubmissionStatus::FINANCE_VALIDATED, $user, 'finance_validated', null, [
                'validated_total_amount' => $locked->financeDetail->validated_total_amount,
            ]);
        });
    }

    public function forwardToApproval(User $user, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission) {
            $locked = FinancialSubmission::query()->with(['financeDetail', 'cooperative', 'submitter'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === SubmissionStatus::FINANCE_REVIEW) {
                if ($locked->isAdvance() && $locked->submitted_by === $user->id) {
                    throw ValidationException::withMessages(['advance' => 'Pembuat panjar tidak dapat meneruskan validasi pengajuannya sendiri.']);
                }
                if (! $locked->financeDetail?->staff_reviewed_at) {
                    throw ValidationException::withMessages(['finance_detail' => 'Review staff keuangan wajib disimpan terlebih dahulu.']);
                }
                $locked->forceFill(['finance_validated_by' => $user->id, 'finance_validated_at' => now()])->save();
                if ($locked->isReimbursement()) {
                    $locked->reimbursementDetail()->update(['finance_validated_amount' => $locked->financeDetail->validated_total_amount, 'finance_notes' => $locked->financeDetail->finance_notes]);
                }
                $this->statuses->transition($locked, SubmissionStatus::FINANCE_VALIDATED, $user, 'finance_validated', $locked->financeDetail->finance_notes, [
                    'validated_total_amount' => $locked->financeDetail->validated_total_amount,
                ]);
                $locked->refresh()->load(['financeDetail', 'cooperative', 'submitter']);
            } else {
                $this->ensureStatus($locked, SubmissionStatus::FINANCE_VALIDATED, 'Hanya pengajuan tervalidasi yang dapat diteruskan.');
            }
            if (! $locked->financeDetail?->validated_total_amount || (float) $locked->financeDetail->validated_total_amount <= 0) {
                throw ValidationException::withMessages(['finance_detail' => 'Nominal validasi wajib diisi.']);
            }

            $locked->forceFill(['forwarded_to_approval_by' => $user->id, 'forwarded_to_approval_at' => now()])->save();
            $this->statuses->transition($locked, SubmissionStatus::APPROVAL_REVIEW, $user, 'forwarded_to_approval');
            $locked->approvalReviews()->create([
                'review_number' => ($locked->approvalReviews()->max('review_number') ?? 0) + 1,
                'status' => ApprovalReviewStatus::PENDING,
                'submitted_amount' => $locked->financeDetail->validated_total_amount,
            ]);

            DB::afterCommit(function () use ($locked, $user) {
                User::role('finance_approver')
                    ->where('is_active', true)
                    ->get()
                    ->each(fn (User $approver) => $approver->notify(new SubmissionForwardedToApprovalNotification($locked->fresh(['financeDetail', 'cooperative', 'submitter']), $user)));
            });

            return $locked->refresh();
        });
    }

    public function rejectSubmission(User $user, FinancialSubmission $submission, string $reason): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission, $reason) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::FINANCE_REVIEW, 'Pengajuan hanya bisa ditolak saat finance review.');
            FinanceSubmissionDetail::updateOrCreate(
                ['financial_submission_id' => $locked->id],
                [
                    'rejection_reason' => $reason,
                    'staff_reviewed_at' => now(),
                    'created_by' => $locked->financeDetail?->created_by ?? $user->id,
                    'updated_by' => $user->id,
                ]
            );

            return $this->statuses->transition($locked, SubmissionStatus::CANCELLED, $user, 'finance_rejected', $reason);
        });
    }

    public function updateApprovalRevision(User $user, FinancialSubmission $submission, array $data): FinanceSubmissionDetail
    {
        return DB::transaction(function () use ($user, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_REVISION_REQUESTED, 'Pengajuan tidak sedang revisi approval.');
            $locked->update([
                'title' => $data['title'],
                'submission_request_category_id' => $data['submission_request_category_id'],
                'submission_request_type_id' => $data['submission_request_type_id'],
                'needed_date' => $data['needed_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            if (isset($data['items'])) {
                $items = collect($data['items'])->map(fn (array $item) => ['request_type_id' => $item['request_type_id'], 'other_type_name' => $item['other_type_name'] ?? null, 'description' => $item['name'], 'quantity' => 1, 'unit' => 'item', 'unit_price' => $item['amount'], 'notes' => $data['notes'] ?? null])->all();
                $total = $this->items->replaceItems($locked, $items);
                $locked->update(['submission_request_type_id' => $data['items'][0]['request_type_id'], 'total_amount' => $total]);
                $data['amount'] = $total;
            }

            return FinanceSubmissionDetail::updateOrCreate(
                ['financial_submission_id' => $locked->id],
                [
                    'finance_notes' => $data['finance_notes'] ?? null,
                    'validated_total_amount' => $data['amount'],
                    'staff_reviewed_at' => now(),
                    'created_by' => $locked->financeDetail?->created_by ?? $user->id,
                    'updated_by' => $user->id,
                ]
            );
        });
    }

    private function ensureStatus(FinancialSubmission $submission, SubmissionStatus $status, string $message): void
    {
        if ($submission->status !== $status) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
