<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionForwardedToApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinancialSubmission $submission, private readonly User $staff) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'cooperative' => $this->submission->cooperative?->name,
            'pic' => $this->submission->submitter?->name,
            'validated_total_amount' => $this->submission->financeDetail?->validated_total_amount,
            'finance_staff' => $this->staff->name,
            'needed_date' => $this->submission->needed_date?->toDateString(),
            'url' => route('approval.submissions.show', $this->submission, absolute: false),
            'created_at' => now()->toISOString(),
        ];
    }
}
