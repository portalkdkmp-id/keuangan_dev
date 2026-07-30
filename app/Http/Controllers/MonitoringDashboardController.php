<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
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
            ],
            'byStatus' => FinancialSubmission::selectRaw('status, count(*) as aggregate, sum(total_amount) as amount')->groupBy('status')->orderBy('status')->get(),
        ]);
    }
}
