<?php

namespace App\Services\Director;

use App\Enums\AccountabilityStatus;
use App\Enums\DirectorDecision;
use App\Enums\DirectorReviewStatus;
use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\SubmissionDirectorReview;
use App\Models\User;
use App\Notifications\DirectorDecisionNotification;
use App\Notifications\DirectorRevisionRequestedNotification;
use App\Services\Accountability\AccountabilityClosingService;
use App\Services\Audit\AuditLogService;
use App\Services\Disbursement\DisbursementService;
use App\Services\Submission\SubmissionStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectorSubmissionService
{
    public function __construct(
        private readonly SubmissionStatusService $statuses,
        private readonly DisbursementService $disbursements,
        private readonly AuditLogService $auditLog,
        private readonly AccountabilityClosingService $accountabilityClosing,
    ) {}

    public function startReview(User $actor, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_REVIEW, 'Pengajuan tidak menunggu review Director.');
            $review = $this->activeReview($locked);
            if ($review->status !== DirectorReviewStatus::PENDING) {
                throw ValidationException::withMessages(['director_review' => 'Pengajuan sedang direview Director lain.']);
            }

            $review->update([
                'director_id' => $actor->id,
                'status' => DirectorReviewStatus::IN_REVIEW,
                'started_at' => now(),
            ]);
            $locked->forceFill([
                'director_reviewed_by' => $actor->id,
                'director_review_started_at' => now(),
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::DIRECTOR_IN_REVIEW, $actor, SubmissionAction::START_DIRECTOR_REVIEW->value);
            $this->auditLog->record('director.review_started', 'Director review dimulai.', $locked, [], $this->meta($locked, $review));

            return $locked->refresh();
        });
    }

    public function approvePendingDisbursement(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_IN_REVIEW, 'Pengajuan tidak sedang direview Director.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $this->validateAmount($locked, $data['approved_amount'], $data['notes'] ?? null);

            $review->update([
                'status' => DirectorReviewStatus::APPROVED_PENDING_DISBURSEMENT,
                'decision' => DirectorDecision::APPROVED_PENDING_DISBURSEMENT,
                'approved_amount' => $data['approved_amount'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
                'resolved_at' => now(),
            ]);
            $locked->forceFill([
                'director_decided_by' => $actor->id,
                'director_decided_at' => now(),
                'director_approved_amount' => $data['approved_amount'],
                'disbursement_status' => 'pending',
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::PENDING_DISBURSEMENT, $actor, SubmissionAction::APPROVE_PENDING_DISBURSEMENT->value, $data['notes'] ?? null, $this->meta($locked, $review, ['approved_amount' => $data['approved_amount']]));
            $this->auditLog->record('director.approved_pending_disbursement', 'Director menyetujui dan menunggu pencairan.', $locked, [], $this->meta($locked, $review));

            DB::afterCommit(fn () => $this->notifyRelated($locked->fresh(), new DirectorDecisionNotification($locked->fresh(), $actor, 'Pengajuan telah disetujui Finance Director dan menunggu pencairan.')));

            return $locked->refresh();
        });
    }

    public function approveAndDisburse(User $actor, FinancialSubmission $submission, array $data, array $files): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data, $files) {
            $locked = FinancialSubmission::query()->with('recipientBankAccount')->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_IN_REVIEW, 'Pengajuan tidak sedang direview Director.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $this->validateAmount($locked, $data['approved_amount'], $data['notes'] ?? null);

            $review->update([
                'status' => DirectorReviewStatus::APPROVED_AND_DISBURSED,
                'decision' => DirectorDecision::APPROVED_AND_DISBURSED,
                'approved_amount' => $data['approved_amount'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
                'resolved_at' => now(),
            ]);
            $disbursement = $this->disbursements->createCompleted($actor, $locked, $review, [
                ...$data,
                'amount' => $data['approved_amount'],
            ], $files);
            $locked->forceFill([
                'director_decided_by' => $actor->id,
                'director_decided_at' => now(),
                'director_approved_amount' => $data['approved_amount'],
                'disbursement_status' => 'completed',
                'disbursed_at' => $disbursement->transferred_at,
                'disbursed_amount' => $disbursement->amount,
                'disbursed_by' => $actor->id,
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::FUND_DISBURSED, $actor, SubmissionAction::APPROVE_AND_DISBURSE->value, $data['notes'] ?? null, $this->meta($locked, $review, ['disbursement_id' => $disbursement->id]));
            $this->settleReimbursement($locked, $disbursement->amount, $disbursement->transferred_at);

            return $locked->refresh();
        });
    }

    public function disburseApprovedSubmission(User $actor, FinancialSubmission $submission, array $data, array $files): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data, $files) {
            $locked = FinancialSubmission::query()->with('recipientBankAccount')->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::PENDING_DISBURSEMENT, 'Pengajuan tidak menunggu pencairan.');
            if (! $locked->director_approved_amount) {
                throw ValidationException::withMessages(['amount' => 'Nominal approval Director belum tersedia.']);
            }
            $review = $locked->directorReviews()->where('decision', DirectorDecision::APPROVED_PENDING_DISBURSEMENT)->orderByDesc('review_number')->lockForUpdate()->firstOrFail();
            $disbursement = $this->disbursements->createCompleted($actor, $locked, $review, [
                ...$data,
                'amount' => $locked->director_approved_amount,
            ], $files);
            $locked->forceFill([
                'disbursement_status' => 'completed',
                'disbursed_at' => $disbursement->transferred_at,
                'disbursed_amount' => $disbursement->amount,
                'disbursed_by' => $actor->id,
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::FUND_DISBURSED, $actor, SubmissionAction::DISBURSE_APPROVED_SUBMISSION->value, $data['notes'] ?? null, ['disbursement_id' => $disbursement->id]);
            $this->settleReimbursement($locked, $disbursement->amount, $disbursement->transferred_at);

            return $locked->refresh();
        });
    }

    public function reject(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_IN_REVIEW, 'Pengajuan tidak sedang direview Director.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $review->update([
                'status' => DirectorReviewStatus::REJECTED,
                'decision' => DirectorDecision::REJECTED,
                'rejection_reason' => $data['rejection_reason'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
                'resolved_at' => now(),
            ]);
            $locked->forceFill([
                'director_decided_by' => $actor->id,
                'director_decided_at' => now(),
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::DIRECTOR_REJECTED, $actor, SubmissionAction::REJECT_BY_DIRECTOR->value, $data['rejection_reason'], $this->meta($locked, $review));

            DB::afterCommit(fn () => $this->notifyRelated($locked->fresh(), new DirectorDecisionNotification($locked->fresh(), $actor, 'Pengajuan ditolak oleh Finance Director.')));

            return $locked->refresh();
        });
    }

    public function requestRevision(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_IN_REVIEW, 'Pengajuan tidak sedang direview Director.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $review->update([
                'status' => DirectorReviewStatus::REVISION_REQUESTED,
                'decision' => DirectorDecision::REVISION_REQUESTED,
                'revision_subject' => $data['revision_subject'],
                'revision_message' => $data['revision_message'],
                'revision_fields' => $data['revision_fields'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
            ]);
            $locked->forceFill([
                'director_revision_count' => $locked->director_revision_count + 1,
                'last_director_revision_requested_at' => now(),
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::DIRECTOR_REVISION_REQUESTED, $actor, SubmissionAction::REQUEST_DIRECTOR_REVISION->value, $data['revision_message'], $this->meta($locked, $review));

            DB::afterCommit(fn () => $this->notifyApprovers($locked->fresh(), new DirectorRevisionRequestedNotification($locked->fresh(), $review->fresh(), $actor)));

            return $locked->refresh();
        });
    }

    private function activeReview(FinancialSubmission $submission): SubmissionDirectorReview
    {
        $review = $submission->directorReviews()->whereIn('status', [
            DirectorReviewStatus::PENDING,
            DirectorReviewStatus::IN_REVIEW,
            DirectorReviewStatus::REVISION_REQUESTED,
        ])->lockForUpdate()->first();

        if (! $review) {
            throw ValidationException::withMessages(['director_review' => 'Director review aktif tidak ditemukan.']);
        }

        return $review;
    }

    private function settleReimbursement(FinancialSubmission $submission, mixed $amount, mixed $paidAt): void
    {
        if (! $submission->isReimbursement() || ! $submission->reimbursementDetail) {
            return;
        }

        $detail = $submission->reimbursementDetail;
        $detail->update(['director_approved_amount' => $submission->director_approved_amount, 'paid_amount' => $amount, 'paid_at' => $paidAt]);
        if ($detail->sourceAccountability && $detail->sourceAccountability->status === AccountabilityStatus::REIMBURSEMENT_PENDING) {
            $this->accountabilityClosing->close($detail->sourceAccountability);
        }
        $this->auditLog->record('reimbursement.paid', 'Reimbursement telah dibayar.', $submission, [], ['paid_amount' => $amount]);
    }

    private function ensureStatus(FinancialSubmission $submission, SubmissionStatus $status, string $message): void
    {
        if ($submission->status !== $status) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function ensureOwnedReview(SubmissionDirectorReview $review, User $actor): void
    {
        if ($review->director_id !== $actor->id || $review->status !== DirectorReviewStatus::IN_REVIEW) {
            throw ValidationException::withMessages(['director_review' => 'Pengajuan sedang direview oleh Director lain.']);
        }
    }

    private function validateAmount(FinancialSubmission $submission, mixed $amount, ?string $notes): void
    {
        $approvalAmount = (float) $submission->approval_approved_amount;
        if ((float) $amount > $approvalAmount) {
            throw ValidationException::withMessages(['approved_amount' => 'Nominal Director tidak boleh lebih besar dari nominal Finance Approval.']);
        }
        if ((float) $amount < $approvalAmount && blank($notes)) {
            throw ValidationException::withMessages(['notes' => 'Catatan wajib diisi jika nominal Director lebih kecil.']);
        }
    }

    private function notifyRelated(FinancialSubmission $submission, object $notification): void
    {
        $users = collect([$submission->submitter]);
        foreach (['finance_reviewed_by', 'finance_validated_by', 'approval_decided_by', 'forwarded_to_director_by'] as $field) {
            if ($submission->{$field}) {
                $users->push(User::find($submission->{$field}));
            }
        }
        $users->filter()->unique('id')->each(fn (User $user) => $user->notify($notification));
    }

    private function notifyApprovers(FinancialSubmission $submission, object $notification): void
    {
        $users = collect();
        foreach (['approval_decided_by', 'forwarded_to_director_by'] as $field) {
            if ($submission->{$field}) {
                $users->push(User::find($submission->{$field}));
            }
        }
        if ($users->filter()->isEmpty()) {
            $users = User::role('finance_approver')->where('is_active', true)->get();
        }
        $users->filter()->unique('id')->each(fn (User $user) => $user->notify($notification));
    }

    private function meta(FinancialSubmission $submission, SubmissionDirectorReview $review, array $extra = []): array
    {
        return [
            ...$extra,
            'submission_id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'director_review_id' => $review->id,
            'review_number' => $review->review_number,
            'status_after' => $submission->status->value,
            'decision' => $review->decision?->value,
        ];
    }
}
