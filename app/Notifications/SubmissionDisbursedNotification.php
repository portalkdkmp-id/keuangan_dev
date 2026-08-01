<?php

namespace App\Notifications;

use App\Models\SubmissionDisbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionDisbursedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SubmissionDisbursement $disbursement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $submission = $this->disbursement->submission;

        return [
            'type' => 'submission_disbursed',
            'title' => 'Dana Pengajuan Dicairkan',
            'message' => "Dana {$submission->submission_number} telah dicairkan kepada {$this->disbursement->recipient_name_snapshot}.",
            'submission_id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'disbursement_id' => $this->disbursement->id,
            'disbursement_number' => $this->disbursement->disbursement_number,
            'amount' => $this->disbursement->amount,
            'recipient_type' => $this->disbursement->recipient_type->value,
            'recipient_name' => $this->disbursement->recipient_name_snapshot,
            'destination_account_masked' => $this->mask($this->disbursement->destination_account_number_snapshot),
            'transfer_date' => $this->disbursement->transfer_date->toDateString(),
            'status' => 'fund_disbursed',
            'url' => "/submissions/{$submission->id}",
        ];
    }

    private function mask(string $number): string
    {
        return str_repeat('*', max(strlen($number) - 4, 0)).substr($number, -4);
    }
}
