<?php

namespace App\Services\Approval;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalReviewStatus;
use App\Enums\DirectorReviewStatus;
use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\SubmissionApprovalReview;
use App\Models\SubmissionDirectorReview;
use App\Models\User;
use App\Notifications\ApprovalResubmittedNotification;
use App\Notifications\ApprovalRevisionRequestedNotification;
use App\Notifications\SubmissionApprovedByFinanceApproverNotification;
use App\Notifications\SubmissionForwardedToDirectorNotification;
use App\Notifications\SubmissionRejectedByFinanceApproverNotification;
use App\Services\Audit\AuditLogService;
use App\Services\FinanceSubmission\FinanceSubmissionService;
use App\Services\Submission\SubmissionStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceApprovalService
{
    public function __construct(
        private readonly SubmissionStatusService $statuses,
        private readonly FinanceSubmissionService $financeSubmissions,
        private readonly AuditLogService $auditLog,
    ) {}

    public function startReview(User $actor, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_REVIEW, 'Pengajuan tidak menunggu approval.');
            $review = $this->activeReview($locked);
            if ($review->status !== ApprovalReviewStatus::PENDING) {
                throw ValidationException::withMessages(['approval' => 'Pengajuan sedang direview oleh approver lain.']);
            }

            $review->update(['approver_id' => $actor->id, 'status' => ApprovalReviewStatus::IN_REVIEW, 'started_at' => now()]);
            $locked->forceFill(['approval_reviewed_by' => $actor->id, 'approval_review_started_at' => now()])->save();
            $this->statuses->transition($locked, SubmissionStatus::APPROVAL_IN_REVIEW, $actor, SubmissionAction::START_APPROVAL_REVIEW->value);
            $this->auditLog->record('approval.review_started', 'Approval review dimulai.', $locked, [], $this->meta($locked, $review));

            return $locked->refresh();
        });
    }

    public function approve(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->with(['submitter', 'cooperative', 'financeDetail'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_IN_REVIEW, 'Pengajuan tidak sedang direview approval.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            if ((float) $data['approved_amount'] > (float) $review->submitted_amount) {
                throw ValidationException::withMessages(['approved_amount' => 'Nominal disetujui tidak boleh lebih besar dari nominal review finance.']);
            }

            $review->update([
                'status' => ApprovalReviewStatus::APPROVED,
                'decision' => ApprovalDecision::APPROVED,
                'approved_amount' => $data['approved_amount'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
                'resolved_at' => now(),
            ]);
            $locked->forceFill([
                'approval_decided_by' => $actor->id,
                'approval_decided_at' => now(),
                'approval_approved_amount' => $data['approved_amount'],
                'forwarded_to_director_by' => $actor->id,
                'forwarded_to_director_at' => now(),
            ])->save();
            $locked->directorReviews()->create([
                'review_number' => 1,
                'status' => DirectorReviewStatus::PENDING,
                'approved_amount' => $data['approved_amount'],
                'notes' => 'Menunggu review Finance Director.',
            ]);
            $this->statuses->transition($locked, SubmissionStatus::DIRECTOR_REVIEW, $actor, SubmissionAction::APPROVE_BY_FINANCE_APPROVER->value, $data['notes'] ?? null, $this->meta($locked, $review, ['approved_amount' => $data['approved_amount']]));
            $this->auditLog->record('approval.approved', 'Pengajuan disetujui finance approver.', $locked, [], $this->meta($locked, $review));

            DB::afterCommit(function () use ($locked, $actor) {
                $fresh = $locked->fresh(['submitter', 'cooperative']);
                $fresh->submitter?->notify(new SubmissionApprovedByFinanceApproverNotification($fresh, $actor));
                $this->notifyFinanceStaff($fresh, new SubmissionApprovedByFinanceApproverNotification($fresh, $actor));
                User::role('finance_director')->where('is_active', true)->get()->each(fn (User $director) => $director->notify(new SubmissionForwardedToDirectorNotification($fresh, $actor)));
            });

            return $locked->refresh();
        });
    }

    public function reject(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->with('submitter')->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_IN_REVIEW, 'Pengajuan tidak sedang direview approval.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $review->update([
                'status' => ApprovalReviewStatus::REJECTED,
                'decision' => ApprovalDecision::REJECTED,
                'rejection_reason' => $data['rejection_reason'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
                'resolved_at' => now(),
            ]);
            $locked->forceFill(['approval_decided_by' => $actor->id, 'approval_decided_at' => now()])->save();
            $this->statuses->transition($locked, SubmissionStatus::APPROVAL_REJECTED, $actor, SubmissionAction::REJECT_BY_FINANCE_APPROVER->value, $data['rejection_reason'], $this->meta($locked, $review));
            $this->auditLog->record('approval.rejected', 'Pengajuan ditolak finance approver.', $locked, [], $this->meta($locked, $review));

            DB::afterCommit(function () use ($locked, $actor, $data) {
                $fresh = $locked->fresh(['submitter']);
                $fresh->submitter?->notify(new SubmissionRejectedByFinanceApproverNotification($fresh, $actor, $data['rejection_reason']));
                $this->notifyFinanceStaff($fresh, new SubmissionRejectedByFinanceApproverNotification($fresh, $actor, $data['rejection_reason']));
            });

            return $locked->refresh();
        });
    }

    public function requestRevision(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_IN_REVIEW, 'Pengajuan tidak sedang direview approval.');
            $review = $this->activeReview($locked);
            $this->ensureOwnedReview($review, $actor);
            $review->update([
                'status' => ApprovalReviewStatus::REVISION_REQUESTED,
                'decision' => ApprovalDecision::REVISION_REQUESTED,
                'revision_subject' => $data['revision_subject'],
                'revision_message' => $data['revision_message'],
                'revision_fields' => $data['revision_fields'],
                'notes' => $data['notes'] ?? null,
                'decided_at' => now(),
            ]);
            $locked->forceFill([
                'approval_revision_count' => $locked->approval_revision_count + 1,
                'last_approval_revision_requested_at' => now(),
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::APPROVAL_REVISION_REQUESTED, $actor, SubmissionAction::REQUEST_APPROVAL_REVISION->value, $data['revision_message'], $this->meta($locked, $review));
            $this->auditLog->record('approval.revision_requested', 'Approval meminta revisi finance staff.', $locked, [], $this->meta($locked, $review));

            DB::afterCommit(fn () => $this->notifyFinanceStaff($locked->fresh(), new ApprovalRevisionRequestedNotification($locked->fresh(), $review->fresh(), $actor)));

            return $locked->refresh();
        });
    }

    public function updateApprovalRevision(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        $this->financeSubmissions->updateApprovalRevision($actor, $submission, $data);
        $this->auditLog->record('approval.revision_updated', 'Perbaikan revisi approval disimpan.', $submission, [], ['submission_id' => $submission->id]);

        return $submission->refresh();
    }

    public function resubmitToApproval(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->with(['financeDetail', 'openRevisionRequest'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::APPROVAL_REVISION_REQUESTED, 'Pengajuan tidak sedang revisi approval.');
            if ($locked->openRevisionRequest()->exists()) {
                throw ValidationException::withMessages(['revision' => 'Masih ada revisi PIC aktif.']);
            }
            $review = $this->activeReview($locked);
            if ($review->status !== ApprovalReviewStatus::REVISION_REQUESTED) {
                throw ValidationException::withMessages(['approval' => 'Tidak ada revisi approval aktif.']);
            }
            if (! $locked->financeDetail?->validated_total_amount || (float) $locked->financeDetail->validated_total_amount <= 0) {
                throw ValidationException::withMessages(['finance_detail' => 'Review finance belum lengkap.']);
            }
            $review->update(['status' => ApprovalReviewStatus::SUPERSEDED, 'resolved_at' => now(), 'change_summary' => $data['change_summary']]);
            $next = $locked->approvalReviews()->create([
                'review_number' => $review->review_number + 1,
                'status' => ApprovalReviewStatus::PENDING,
                'submitted_amount' => $locked->financeDetail->validated_total_amount,
                'notes' => $data['notes'] ?? null,
            ]);
            $locked->forceFill([
                'last_approval_resubmitted_at' => now(),
                'approval_reviewed_by' => null,
                'approval_review_started_at' => null,
            ])->save();
            $this->statuses->transition($locked, SubmissionStatus::APPROVAL_REVIEW, $actor, SubmissionAction::RESUBMIT_TO_APPROVAL->value, $data['notes'] ?? null, ['change_summary' => $data['change_summary'], 'review_number' => $next->review_number]);
            $this->auditLog->record('approval.resubmitted', 'Pengajuan dikirim ulang ke finance approval.', $locked, [], $this->meta($locked, $next));

            DB::afterCommit(fn () => User::role('finance_approver')->where('is_active', true)->get()->each(fn (User $approver) => $approver->notify(new ApprovalResubmittedNotification($locked->fresh(), $next->fresh(), $actor, $data['change_summary']))));

            return $locked->refresh();
        });
    }

    public function resubmitToDirector(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, SubmissionStatus::DIRECTOR_REVISION_REQUESTED, 'Pengajuan tidak sedang revisi Director.');

            $directorReview = $this->activeDirectorReview($locked);
            if ($directorReview->status !== DirectorReviewStatus::REVISION_REQUESTED) {
                throw ValidationException::withMessages(['director_review' => 'Tidak ada revisi Director aktif.']);
            }

            $approvalReview = $locked->approvalReviews()->where('status', ApprovalReviewStatus::APPROVED)->orderByDesc('review_number')->lockForUpdate()->first();
            if (! $approvalReview?->approved_amount) {
                throw ValidationException::withMessages(['approval' => 'Nominal approval belum tersedia.']);
            }

            $directorReview->update([
                'status' => DirectorReviewStatus::SUPERSEDED,
                'resolved_at' => now(),
                'change_summary' => $data['change_summary'],
            ]);

            $next = $locked->directorReviews()->create([
                'review_number' => $directorReview->review_number + 1,
                'status' => DirectorReviewStatus::PENDING,
                'approved_amount' => $approvalReview->approved_amount,
                'notes' => $data['notes'] ?? null,
            ]);

            $locked->forceFill([
                'last_director_resubmitted_at' => now(),
                'director_reviewed_by' => null,
                'director_review_started_at' => null,
            ])->save();

            $this->statuses->transition($locked, SubmissionStatus::DIRECTOR_REVIEW, $actor, SubmissionAction::RESUBMIT_TO_DIRECTOR->value, $data['notes'] ?? null, [
                'change_summary' => $data['change_summary'],
                'review_number' => $next->review_number,
            ]);
            $this->auditLog->record('director.resubmitted', 'Pengajuan dikirim ulang ke Finance Director.', $locked, [], [
                'submission_id' => $locked->id,
                'director_review_id' => $next->id,
                'review_number' => $next->review_number,
            ]);

            DB::afterCommit(fn () => User::role('finance_director')->where('is_active', true)->get()->each(fn (User $director) => $director->notify(new ApprovalResubmittedNotification($locked->fresh(), $approvalReview->fresh(), $actor, $data['change_summary']))));

            return $locked->refresh();
        });
    }

    private function activeReview(FinancialSubmission $submission): SubmissionApprovalReview
    {
        $review = $submission->approvalReviews()->whereIn('status', [
            ApprovalReviewStatus::PENDING,
            ApprovalReviewStatus::IN_REVIEW,
            ApprovalReviewStatus::REVISION_REQUESTED,
        ])->lockForUpdate()->first();

        if (! $review) {
            throw ValidationException::withMessages(['approval' => 'Approval review aktif tidak ditemukan.']);
        }

        return $review;
    }

    private function activeDirectorReview(FinancialSubmission $submission): SubmissionDirectorReview
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

    private function ensureStatus(FinancialSubmission $submission, SubmissionStatus $status, string $message): void
    {
        if ($submission->status !== $status) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function ensureOwnedReview(SubmissionApprovalReview $review, User $actor): void
    {
        if ($review->approver_id !== $actor->id || $review->status !== ApprovalReviewStatus::IN_REVIEW) {
            throw ValidationException::withMessages(['approval' => 'Pengajuan sedang direview oleh approver lain.']);
        }
    }

    private function notifyFinanceStaff(FinancialSubmission $submission, object $notification): void
    {
        $users = collect();
        foreach (['forwarded_to_approval_by', 'finance_reviewed_by', 'finance_validated_by'] as $field) {
            if ($submission->{$field}) {
                $users->push(User::find($submission->{$field}));
            }
        }
        if ($users->filter()->isEmpty()) {
            $users = User::role('finance_staff')->where('is_active', true)->get();
        }
        $users->filter()->unique('id')->each(fn (User $user) => $user->notify($notification));
    }

    private function meta(FinancialSubmission $submission, SubmissionApprovalReview $review, array $extra = []): array
    {
        return [
            ...$extra,
            'submission_id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'approval_review_id' => $review->id,
            'review_number' => $review->review_number,
            'status_after' => $submission->status->value,
            'submitted_amount' => $review->submitted_amount,
            'decision' => $review->decision?->value,
        ];
    }
}
