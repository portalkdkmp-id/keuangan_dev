<?php

namespace App\Notifications;

use App\Models\FinancialSubmission;
use App\Models\SubmissionRevisionRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionRevisionRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinancialSubmission $submission, private readonly SubmissionRevisionRequest $revisionRequest, private readonly User $staff) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'revision_request_id' => $this->revisionRequest->id,
            'revision_number' => $this->revisionRequest->revision_number,
            'subject' => $this->revisionRequest->subject,
            'requested_by' => $this->staff->name,
            'fields' => $this->revisionRequest->fields,
            'url' => route('submissions.revision.edit', $this->submission, absolute: false),
            'created_at' => now()->toISOString(),
        ];
    }
}
