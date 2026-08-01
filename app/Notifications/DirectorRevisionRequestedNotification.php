<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\SubmissionDirectorReview;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DirectorRevisionRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FinancialSubmission $submission,
        private readonly SubmissionDirectorReview $review,
        private readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'director_revision_requested',
            'title' => 'Revisi dari Finance Director',
            'message' => $this->review->revision_subject,
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'director_review_id' => $this->review->id,
            'revision_message' => $this->review->revision_message,
            'revision_fields' => $this->review->revision_fields,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }
}
