<?php

namespace App\Services\Monitoring;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;

class DirectorMonitoringService
{
    public function summary(): array
    {
        $statuses = [
            SubmissionStatus::DIRECTOR_REVIEW,
            SubmissionStatus::DIRECTOR_IN_REVIEW,
            SubmissionStatus::DIRECTOR_REVISION_REQUESTED,
            SubmissionStatus::PENDING_DISBURSEMENT,
            SubmissionStatus::FUND_DISBURSED,
            SubmissionStatus::DIRECTOR_REJECTED,
        ];

        $counts = FinancialSubmission::query()
            ->whereIn('status', $statuses)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'waiting_review' => (int) ($counts[SubmissionStatus::DIRECTOR_REVIEW->value] ?? 0),
            'in_review' => (int) ($counts[SubmissionStatus::DIRECTOR_IN_REVIEW->value] ?? 0),
            'revision_requested' => (int) ($counts[SubmissionStatus::DIRECTOR_REVISION_REQUESTED->value] ?? 0),
            'pending_disbursement' => (int) ($counts[SubmissionStatus::PENDING_DISBURSEMENT->value] ?? 0),
            'fund_disbursed' => (int) ($counts[SubmissionStatus::FUND_DISBURSED->value] ?? 0),
            'rejected' => (int) ($counts[SubmissionStatus::DIRECTOR_REJECTED->value] ?? 0),
            'pending_disbursement_amount' => FinancialSubmission::query()->where('status', SubmissionStatus::PENDING_DISBURSEMENT)->sum('director_approved_amount'),
            'month_disbursed_amount' => FinancialSubmission::query()->where('status', SubmissionStatus::FUND_DISBURSED)->whereMonth('disbursed_at', now()->month)->whereYear('disbursed_at', now()->year)->sum('disbursed_amount'),
            'today_disbursed' => FinancialSubmission::query()->where('status', SubmissionStatus::FUND_DISBURSED)->whereDate('disbursed_at', today())->count(),
        ];
    }

    public function actionable()
    {
        return FinancialSubmission::query()
            ->whereIn('status', [SubmissionStatus::DIRECTOR_REVIEW, SubmissionStatus::DIRECTOR_IN_REVIEW, SubmissionStatus::PENDING_DISBURSEMENT])
            ->with(['cooperative', 'submitter'])
            ->orderBy('needed_date')
            ->limit(10)
            ->get();
    }
}
