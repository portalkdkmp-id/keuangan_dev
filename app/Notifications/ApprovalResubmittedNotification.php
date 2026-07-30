<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\SubmissionApprovalReview;
use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ApprovalResubmittedNotification extends Notification
{
    public function __construct(private readonly FinancialSubmission $submission, private readonly SubmissionApprovalReview $review, private readonly User $staff, private readonly string $changeSummary) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'approval_resubmitted',
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'review_number' => $this->review->review_number,
            'finance_staff' => $this->staff->name,
            'change_summary' => $this->changeSummary,
            'amount' => $this->review->submitted_amount,
            'url' => route('approval.submissions.show', $this->submission, absolute: false),
        ]);
    }
}
