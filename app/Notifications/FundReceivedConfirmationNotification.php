<?php

namespace App\Notifications;

use App\Models\FundReceiptConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FundReceivedConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FundReceiptConfirmation $receipt) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'fund_received_confirmed', 'title' => 'Penerimaan Dana Dikonfirmasi', 'message' => "Dana {$this->receipt->submission->submission_number} telah dikonfirmasi diterima.", 'submission_id' => $this->receipt->financial_submission_id, 'amount' => $this->receipt->amount, 'received_at' => $this->receipt->received_at->toIso8601String(), 'url' => "/submissions/{$this->receipt->financial_submission_id}"];
    }
}
