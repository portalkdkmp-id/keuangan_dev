<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DirectorDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FinancialSubmission $submission,
        private readonly User $actor,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'director_decision',
            'title' => 'Keputusan Finance Director',
            'message' => $this->message,
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'status' => $this->submission->status->value,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }
}
