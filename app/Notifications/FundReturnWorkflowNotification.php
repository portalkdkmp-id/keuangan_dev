<?php

namespace App\Notifications;

use App\Models\FundReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FundReturnWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(private FundReturn $fundReturn, private string $title, private string $url) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'fund_return', 'title' => $this->title, 'message' => "Pengembalian {$this->fundReturn->return_number} berstatus {$this->fundReturn->status->value}.", 'fund_return_id' => $this->fundReturn->id, 'status' => $this->fundReturn->status->value, 'url' => $this->url];
    }
}
