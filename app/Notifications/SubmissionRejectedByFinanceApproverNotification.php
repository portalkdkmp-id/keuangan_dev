<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SubmissionRejectedByFinanceApproverNotification extends Notification
{
    public function __construct(private readonly FinancialSubmission $submission, private readonly User $approver, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'submission_rejected_by_finance_approver',
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'rejection_reason' => $this->reason,
            'approver' => $this->approver->name,
            'status' => $this->submission->status->value,
            'url' => route('submissions.show', $this->submission, absolute: false),
        ]);
    }
}
