<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\SubmissionApprovalReview;
use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ApprovalRevisionRequestedNotification extends Notification
{
    public function __construct(private readonly FinancialSubmission $submission, private readonly SubmissionApprovalReview $review, private readonly User $approver) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'approval_revision_requested',
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'approval_review_id' => $this->review->id,
            'review_number' => $this->review->review_number,
            'approver' => $this->approver->name,
            'revision_subject' => $this->review->revision_subject,
            'revision_fields' => $this->review->revision_fields,
            'url' => route('finance.approval-revisions.show', $this->submission, absolute: false),
        ]);
    }
}
