<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FinancialSubmission $submission,
        private readonly User $actor,
        private readonly string $reason,
        private readonly string $stage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'submission_rejected',
            'title' => 'Pengajuan Ditolak',
            'message' => "Pengajuan ditolak oleh {$this->stage}.",
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'rejection_reason' => $this->reason,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'status' => $this->submission->status->value,
            'url' => route('submission-history.show', $this->submission, absolute: false),
        ];
    }
}
