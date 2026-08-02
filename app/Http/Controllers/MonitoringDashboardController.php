<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\FundDistribution;
use App\Models\FundReceiptConfirmation;
use App\Models\FundReturn;
use App\Models\ReimbursementDetail;
use App\Models\SubmissionDisbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringDashboardController extends Controller
{
    public function finance(Request $request): Response
    {
        Gate::authorize('finance-monitoring.view');

        return Inertia::render('Monitoring/Finance', [
            'stats' => [
                'new' => FinancialSubmission::where('status', SubmissionStatus::SUBMITTED)->count(),
                'in_review' => FinancialSubmission::where('status', SubmissionStatus::FINANCE_REVIEW)->count(),
                'pic_revision' => FinancialSubmission::where('status', SubmissionStatus::REVISION_REQUESTED)->count(),
                'approval_revision' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REVISION_REQUESTED)->count(),
                'waiting_approval' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REVIEW)->count(),
                'approved' => FinancialSubmission::where('status', SubmissionStatus::DIRECTOR_REVIEW)->count(),
                'rejected' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REJECTED)->count(),
                'active_amount' => FinancialSubmission::whereNotIn('status', [SubmissionStatus::CANCELLED, SubmissionStatus::APPROVAL_REJECTED])->sum('total_amount'),
                'dana_masuk_director' => SubmissionDisbursement::where('recipient_type', 'finance_staff')->count(),
                'menunggu_distribusi' => SubmissionDisbursement::where('distribution_status', 'pending')->count(),
                'distribusi_sebagian' => SubmissionDisbursement::where('distribution_status', 'partially_distributed')->count(),
                'pic_belum_konfirmasi' => SubmissionDisbursement::whereIn('distribution_status', ['not_required', 'fully_distributed'])->count(),
                'laporan_menunggu_review' => FundAccountabilityReport::where('status', 'submitted')->count(),
                'revisi_laporan' => FundAccountabilityReport::where('status', 'revision_requested')->count(),
                'laporan_terverifikasi' => FundAccountabilityReport::where('status', 'finance_verified')->count(),
                'reimbursement_baru' => FinancialSubmission::where('type', 'reimbursement')->where('status', SubmissionStatus::SUBMITTED)->count(),
                'fund_return_baru' => FundReturn::where('status', 'submitted')->count(),
                'fund_return_review' => FundReturn::where('status', 'finance_review')->count(),
            ],
            'needsAction' => FinancialSubmission::whereIn('status', [SubmissionStatus::SUBMITTED, SubmissionStatus::FINANCE_REVIEW, SubmissionStatus::APPROVAL_REVISION_REQUESTED])->with(['cooperative', 'submitter'])->latest()->limit(8)->get(),
        ]);
    }

    public function approval(Request $request): Response
    {
        Gate::authorize('approval-monitoring.view');

        return Inertia::render('Monitoring/Approval', [
            'stats' => [
                'pending' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REVIEW)->count(),
                'in_review' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_IN_REVIEW)->count(),
                'revision' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REVISION_REQUESTED)->count(),
                'approved_today' => FinancialSubmission::where('status', SubmissionStatus::DIRECTOR_REVIEW)->whereDate('approval_decided_at', today())->count(),
                'rejected_today' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REJECTED)->whereDate('approval_decided_at', today())->count(),
                'pending_amount' => FinancialSubmission::whereIn('status', [SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW])->sum('total_amount'),
                'approved_amount_month' => FinancialSubmission::where('status', SubmissionStatus::DIRECTOR_REVIEW)->whereMonth('approval_decided_at', now()->month)->sum('approval_approved_amount'),
                'overdue' => FinancialSubmission::whereDate('needed_date', '<', today())->whereNotIn('status', [SubmissionStatus::CANCELLED, SubmissionStatus::APPROVAL_REJECTED])->count(),
                'disbursed_today' => SubmissionDisbursement::whereDate('transferred_at', today())->count(),
                'disbursed_month' => SubmissionDisbursement::whereMonth('transferred_at', now()->month)->whereYear('transferred_at', now()->year)->sum('amount'),
                'waiting_distribution' => SubmissionDisbursement::where('distribution_status', 'pending')->count(),
                'partial_distribution' => SubmissionDisbursement::where('distribution_status', 'partially_distributed')->count(),
                'waiting_pic_confirmation' => SubmissionDisbursement::whereIn('distribution_status', ['not_required', 'fully_distributed'])->count(),
                'waiting_accountability' => SubmissionDisbursement::where('distribution_status', 'accountability_pending')->count(),
                'accountability_submitted' => FundAccountabilityReport::whereIn('status', ['submitted', 'finance_review', 'finance_verified'])->count(),
                'accountability_approved' => FundAccountabilityReport::where('status', 'closed')->count(),
                'reimbursement_pending' => FinancialSubmission::where('type', 'reimbursement')->whereIn('status', [SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW])->count(),
                'fund_return_pending' => FundReturn::where('status', 'finance_verified')->count(),
            ],
            'oldest' => FinancialSubmission::whereIn('status', [SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW])->with(['cooperative', 'submitter'])->orderBy('forwarded_to_approval_at')->limit(8)->get(),
        ]);
    }

    public function global(Request $request): Response
    {
        Gate::authorize('global-monitoring.view');

        return Inertia::render('Monitoring/Global', [
            'stats' => [
                'total' => FinancialSubmission::count(),
                'total_amount' => FinancialSubmission::sum('total_amount'),
                'pending_finance' => FinancialSubmission::whereIn('status', [SubmissionStatus::SUBMITTED, SubmissionStatus::FINANCE_REVIEW])->count(),
                'pending_approval' => FinancialSubmission::whereIn('status', [SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW])->count(),
                'pending_director' => FinancialSubmission::where('status', SubmissionStatus::DIRECTOR_REVIEW)->count(),
                'approved' => FinancialSubmission::where('status', SubmissionStatus::DIRECTOR_REVIEW)->count(),
                'rejected' => FinancialSubmission::where('status', SubmissionStatus::APPROVAL_REJECTED)->count(),
                'cancelled' => FinancialSubmission::where('status', SubmissionStatus::CANCELLED)->count(),
                'overdue' => FinancialSubmission::whereDate('needed_date', '<', today())->whereNotIn('status', [SubmissionStatus::CANCELLED, SubmissionStatus::APPROVAL_REJECTED])->count(),
                'total_disbursed' => SubmissionDisbursement::sum('amount'),
                'total_distributed' => FundDistribution::where('status', '!=', 'cancelled')->sum('amount'),
                'total_confirmed' => FundReceiptConfirmation::sum('amount'),
                'total_realized' => FundAccountabilityReport::sum('realized_amount'),
                'total_remaining' => FundAccountabilityReport::sum('remaining_amount'),
                'total_additional' => FundAccountabilityReport::sum('additional_amount'),
                'reimbursement_claimed' => ReimbursementDetail::sum('claimed_amount'),
                'reimbursement_paid' => ReimbursementDetail::sum('paid_amount'),
                'reimbursement_outstanding' => ReimbursementDetail::whereNull('paid_at')->sum('director_approved_amount'),
                'fund_return_total' => FundReturn::where('status', 'closed')->sum('returned_amount'),
                'fund_return_outstanding' => FundReturn::whereNot('status', 'closed')->sum('expected_amount'),
                'accountability_settlement_rate' => FundAccountabilityReport::whereIn('status', ['approved', 'return_pending', 'reimbursement_pending', 'closed'])->count() > 0
                    ? round(FundAccountabilityReport::where('status', 'closed')->count() / FundAccountabilityReport::whereIn('status', ['approved', 'return_pending', 'reimbursement_pending', 'closed'])->count() * 100, 2) : 0,
            ],
            'byStatus' => FinancialSubmission::selectRaw('status, count(*) as aggregate, sum(total_amount) as amount')->groupBy('status')->orderBy('status')->get(),
        ]);
    }
}
