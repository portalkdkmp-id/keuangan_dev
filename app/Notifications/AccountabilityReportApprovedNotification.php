<?php

namespace App\Notifications;

use App\Models\FundAccountabilityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountabilityReportApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FundAccountabilityReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'accountability_approved', 'title' => 'Pertanggungjawaban Disetujui', 'message' => "Laporan {$this->report->report_number} telah disetujui dan ditutup.", 'report_id' => $this->report->id, 'submission_id' => $this->report->financial_submission_id, 'status' => $this->report->status->value, 'url' => "/accountability-reports/{$this->report->id}"];
    }
}
