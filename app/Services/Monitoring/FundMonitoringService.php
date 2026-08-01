<?php

namespace App\Services\Monitoring;

use App\Models\FundAccountabilityReport;
use App\Models\FundDistribution;
use App\Models\FundReceiptConfirmation;
use App\Models\SubmissionDisbursement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundMonitoringService
{
    public function data(Request $request): array
    {
        $disbursements = $this->filteredDisbursements($request);
        $distributions = FundDistribution::query()->whereIn('submission_disbursement_id', (clone $disbursements)->select('submission_disbursements.id'))->where('status', '!=', 'cancelled');
        $receipts = FundReceiptConfirmation::query()->whereIn('financial_submission_id', (clone $disbursements)->select('financial_submission_id'));
        $reports = FundAccountabilityReport::query()->whereIn('financial_submission_id', (clone $disbursements)->select('financial_submission_id'));
        $requires = (clone $disbursements)->where('requires_distribution', true)->count();
        $fully = (clone $disbursements)->where('distribution_status', 'fully_distributed')->count();
        $confirmed = (clone $receipts)->count();
        $submitted = (clone $reports)->whereNotIn('status', ['draft', 'revision_requested'])->count();
        $approved = (clone $reports)->whereIn('status', ['approved', 'closed'])->count();
        $distributionTimes = FundDistribution::query()
            ->whereIn('submission_disbursement_id', (clone $disbursements)->select('submission_disbursements.id'))
            ->where('status', '!=', 'cancelled')
            ->selectRaw('submission_disbursement_id, max(transferred_at) as last_transferred_at')
            ->groupBy('submission_disbursement_id');
        $receiptTimes = FundReceiptConfirmation::query()
            ->whereIn('financial_submission_id', (clone $disbursements)->select('financial_submission_id'))
            ->selectRaw('financial_submission_id, min(received_at) as first_received_at')
            ->groupBy('financial_submission_id');
        $isPostgres = DB::getDriverName() === 'pgsql';
        $averageDistributionSeconds = DB::query()->fromSub($distributionTimes, 'distribution_times')
            ->join('submission_disbursements', 'submission_disbursements.id', '=', 'distribution_times.submission_disbursement_id')
            ->avg(DB::raw($isPostgres ? 'extract(epoch from (distribution_times.last_transferred_at - submission_disbursements.transferred_at))' : '(julianday(distribution_times.last_transferred_at) - julianday(submission_disbursements.transferred_at)) * 86400'));
        $averageAccountabilitySeconds = DB::query()->fromSub($receiptTimes, 'receipt_times')
            ->join('fund_accountability_reports', 'fund_accountability_reports.financial_submission_id', '=', 'receipt_times.financial_submission_id')
            ->whereNotNull('fund_accountability_reports.submitted_at')
            ->avg(DB::raw($isPostgres ? 'extract(epoch from (fund_accountability_reports.submitted_at - receipt_times.first_received_at))' : '(julianday(fund_accountability_reports.submitted_at) - julianday(receipt_times.first_received_at)) * 86400'));

        return [
            'stats' => [
                'total_disbursed' => (clone $disbursements)->sum('amount'),
                'total_distributed' => (clone $distributions)->sum('amount'),
                'total_confirmed' => (clone $receipts)->sum('amount'),
                'total_realized' => (clone $reports)->sum('realized_amount'),
                'total_remaining' => (clone $reports)->sum('remaining_amount'),
                'total_additional' => (clone $reports)->sum('additional_amount'),
                'undistributed_amount' => max((float) (clone $disbursements)->where('requires_distribution', true)->sum('amount') - (float) (clone $distributions)->sum('amount'), 0),
                'unaccounted_amount' => max((float) (clone $receipts)->sum('amount') - (float) (clone $reports)->sum('realized_amount'), 0),
                'waiting_distribution' => (clone $disbursements)->whereIn('distribution_status', ['pending', 'partially_distributed'])->count(),
                'waiting_confirmation' => (clone $disbursements)->whereIn('distribution_status', ['not_required', 'fully_distributed'])->count(),
                'waiting_accountability' => (clone $disbursements)->whereIn('distribution_status', ['accountability_pending', 'recipient_confirmed'])->count(),
                'accountability_submitted' => $submitted,
                'accountability_approved' => $approved,
                'distribution_completion_rate' => $requires ? round($fully / $requires * 100, 2) : 0,
                'accountability_submission_rate' => $confirmed ? round($submitted / $confirmed * 100, 2) : 0,
                'accountability_approval_rate' => $submitted ? round($approved / $submitted * 100, 2) : 0,
                'average_distribution_hours' => round(max((float) $averageDistributionSeconds, 0) / 3600, 2),
                'average_accountability_hours' => round(max((float) $averageAccountabilitySeconds, 0) / 3600, 2),
            ],
            'latestDisbursements' => (clone $disbursements)->with(['submission:id,submission_number,title,cooperative_id,submitted_by', 'submission.cooperative:id,name', 'submission.submitter:id,name', 'recipientUser:id,name'])->latest('transferred_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['date_from', 'date_to', 'province_id', 'city_id', 'cooperative_id', 'pic_id', 'finance_staff_id', 'finance_approver_id', 'director_id', 'category_id', 'submission_type_id', 'recipient_type', 'distribution_status', 'accountability_status']),
        ];
    }

    private function filteredDisbursements(Request $request): Builder
    {
        return SubmissionDisbursement::query()
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('transferred_at', '>=', $v))->when($request->date_to, fn ($q, $v) => $q->whereDate('transferred_at', '<=', $v))
            ->when($request->recipient_type, fn ($q, $v) => $q->where('recipient_type', $v))->when($request->distribution_status, fn ($q, $v) => $q->where('distribution_status', $v))
            ->when($request->finance_staff_id, fn ($q, $v) => $q->where('recipient_user_id', $v))
            ->when($request->director_id, fn ($q, $v) => $q->where('disbursed_by', $v))
            ->whereHas('submission', function ($q) use ($request) {
                $q->when($request->cooperative_id, fn ($x, $v) => $x->where('cooperative_id', $v))
                    ->when($request->pic_id, fn ($x, $v) => $x->where('submitted_by', $v))
                    ->when($request->finance_approver_id, fn ($x, $v) => $x->where('approval_decided_by', $v))
                    ->when($request->category_id, fn ($x, $v) => $x->where('submission_request_category_id', $v))
                    ->when($request->submission_type_id, fn ($x, $v) => $x->where('submission_request_type_id', $v))
                    ->when($request->province_id, fn ($x, $v) => $x->whereHas('cooperative', fn ($c) => $c->where('province_id', $v)))
                    ->when($request->city_id, fn ($x, $v) => $x->whereHas('cooperative', fn ($c) => $c->where('city_id', $v)))
                    ->when($request->accountability_status, fn ($x, $v) => $x->whereHas('accountabilityReport', fn ($a) => $a->where('status', $v)));
            });
    }
}
