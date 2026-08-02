<?php

namespace App\Notifications;

use App\Models\FundDistribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FundDistributionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FundDistribution $distribution) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'fund_distribution_created', 'title' => 'Dana Telah Disalurkan', 'message' => "Distribusi {$this->distribution->distribution_number} telah dikirim.", 'submission_id' => $this->distribution->financial_submission_id, 'distribution_id' => $this->distribution->id, 'amount' => $this->distribution->amount, 'status' => $this->distribution->status->value, 'url' => "/finance/fund-distributions/{$this->distribution->id}"];
    }
}
