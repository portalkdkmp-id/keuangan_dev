<?php

namespace App\Notifications;

use App\Enums\SubmissionStatus;
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
        private readonly ?string $reason = null,
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
            'rejection_reason' => $this->reason,
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        if (in_array($this->submission->status->value, SubmissionStatus::finalValues(), true)) {
            return route('submission-history.show', $this->submission, absolute: false);
        }

        if ($notifiable instanceof User && $this->submission->isOwnedBy($notifiable)) {
            return route('submissions.show', $this->submission, absolute: false);
        }

        if ($notifiable instanceof User && $notifiable->can('director-submissions.view')) {
            return route('director.submissions.show', $this->submission, absolute: false);
        }

        if ($notifiable instanceof User && $notifiable->can('approval-submissions.view')) {
            return route('approval.submissions.show', $this->submission, absolute: false);
        }

        return route('finance.submissions.show', $this->submission, absolute: false);
    }
}
