<?php

namespace App\Services\Submission;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class SubmissionStatusService
{
    private const ALLOWED = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['finance_review'],
        'finance_review' => ['revision_requested', 'finance_validated', 'cancelled'],
        'revision_requested' => ['submitted', 'cancelled'],
        'finance_validated' => ['approval_review'],
        'approval_review' => ['approval_in_review'],
        'approval_in_review' => ['approval_revision_requested', 'approval_rejected', 'director_review'],
        'approval_revision_requested' => ['approval_review'],
    ];

    public function __construct(private readonly AuditLogService $auditLog) {}

    public function transition(FinancialSubmission $submission, SubmissionStatus $targetStatus, User $actor, string $action, ?string $notes = null, array $metadata = []): FinancialSubmission
    {
        $fromStatus = $submission->status;
        if (! in_array($targetStatus->value, self::ALLOWED[$fromStatus->value] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Transisi status tidak diizinkan.']);
        }

        $submission->forceFill([
            'status' => $targetStatus,
            'submitted_at' => $targetStatus === SubmissionStatus::SUBMITTED ? ($submission->submitted_at ?? now()) : $submission->submitted_at,
            'finance_review_started_at' => $targetStatus === SubmissionStatus::FINANCE_REVIEW ? now() : $submission->finance_review_started_at,
            'cancelled_at' => $targetStatus === SubmissionStatus::CANCELLED ? now() : $submission->cancelled_at,
            'current_assignee_role' => match ($targetStatus) {
                SubmissionStatus::SUBMITTED, SubmissionStatus::FINANCE_REVIEW, SubmissionStatus::FINANCE_VALIDATED, SubmissionStatus::APPROVAL_REVISION_REQUESTED => 'finance_staff',
                SubmissionStatus::REVISION_REQUESTED => 'pic_kdkmp',
                SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW => 'finance_approver',
                SubmissionStatus::DIRECTOR_REVIEW => 'finance_director',
                SubmissionStatus::APPROVAL_REJECTED, SubmissionStatus::CANCELLED => null,
                default => $submission->current_assignee_role,
            },
        ])->save();

        $submission->statusHistories()->create([
            'from_status' => $fromStatus,
            'to_status' => $targetStatus,
            'changed_by' => $actor->id,
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->auditLog->record('submission.'.$action, 'Status pengajuan berubah.', $submission, ['status' => $fromStatus->value], [
            'status' => $targetStatus->value,
            'submission_id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'total_amount' => $submission->total_amount,
        ]);

        return $submission->refresh();
    }
}
