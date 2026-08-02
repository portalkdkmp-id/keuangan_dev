<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\Province;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Services\Monitoring\FundMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FundMonitoringController extends Controller
{
    public function __construct(private readonly FundMonitoringService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('fund-monitoring.view');

        return Inertia::render('Monitoring/Funds/Index', [
            ...$this->service->data($request),
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
            'cities' => City::orderBy('name')->get(['id', 'name', 'province_id']),
            'cooperatives' => Cooperative::orderBy('name')->get(['id', 'name']),
            'pics' => User::role('pic_kdkmp')->orderBy('name')->get(['id', 'name']),
            'financeStaff' => User::role('finance_staff')->orderBy('name')->get(['id', 'name']),
            'financeApprovers' => User::role('finance_approver')->orderBy('name')->get(['id', 'name']),
            'directors' => User::role('finance_director')->orderBy('name')->get(['id', 'name']),
            'categories' => SubmissionRequestCategory::orderBy('name')->get(['id', 'name']),
            'submissionTypes' => SubmissionRequestType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('fund-monitoring.view');

        return Inertia::render('Monitoring/Funds/Show', ['submission' => $financialSubmission->load(['cooperative', 'submitter', 'directorDecisionMaker', 'statusHistories.actor', 'disbursement.disburser', 'disbursement.attachments', 'disbursement.distributions.distributor', 'disbursement.distributions.attachments', 'receiptConfirmations.recipient', 'accountabilityReport.submitter', 'accountabilityReport.items', 'accountabilityReport.attachments', 'accountabilityReport.financeReviewer', 'accountabilityReport.approver'])]);
    }
}
