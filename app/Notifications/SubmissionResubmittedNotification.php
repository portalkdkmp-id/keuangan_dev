<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionResubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinancialSubmission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'revision_number' => $this->submission->revision_count,
            'pic' => $this->submission->submitter?->name,
            'cooperative' => $this->submission->cooperative?->name,
            'total_amount' => $this->submission->total_amount,
            'url' => route('finance.submissions.show', $this->submission, absolute: false),
            'created_at' => now()->toISOString(),
        ];
    }
}
