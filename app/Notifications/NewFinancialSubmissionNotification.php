<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewFinancialSubmissionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinancialSubmission $submission, private readonly User $pic) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'cooperative_name' => $this->submission->cooperative?->name,
            'pic_name' => $this->pic->name,
            'total_amount' => $this->submission->total_amount,
            'needed_date' => $this->submission->needed_date?->toDateString(),
            'url' => route('finance.submissions.show', $this->submission, absolute: false),
            'created_at' => now()->toISOString(),
        ];
    }
}
