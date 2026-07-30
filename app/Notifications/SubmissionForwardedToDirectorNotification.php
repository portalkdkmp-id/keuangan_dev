<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SubmissionForwardedToDirectorNotification extends Notification
{
    public function __construct(private readonly FinancialSubmission $submission, private readonly User $approver) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'submission_forwarded_to_director',
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'approved_amount' => $this->submission->approval_approved_amount,
            'approver' => $this->approver->name,
            'pic' => $this->submission->submitter?->name,
            'koperasi' => $this->submission->cooperative?->name,
            'needed_date' => $this->submission->needed_date?->toDateString(),
            'url' => route('director.submissions.show', $this->submission, absolute: false),
        ]);
    }
}
