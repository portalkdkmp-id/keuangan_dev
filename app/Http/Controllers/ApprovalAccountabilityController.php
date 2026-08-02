<?php

namespace App\Http\Controllers;

use App\Enums\AccountabilityStatus;
use App\Models\FundAccountabilityReport;
use App\Services\Accountability\FundAccountabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalAccountabilityController extends Controller
{
    public function __construct(private readonly FundAccountabilityService $service) {}

    public function index(): Response
    {
        Gate::authorize('accountability-reports.approve');

        return Inertia::render('Approval/AccountabilityReports/Index', ['reports' => FundAccountabilityReport::with(['submission:id,submission_number,title,cooperative_id', 'submission.cooperative:id,name', 'submitter:id,name'])->whereIn('status', [AccountabilityStatus::FINANCE_VERIFIED->value, AccountabilityStatus::CLOSED->value])->latest('finance_reviewed_at')->paginate(10)]);
    }

    public function show(FundAccountabilityReport $fundAccountabilityReport): Response
    {
        Gate::authorize('view', $fundAccountabilityReport);

        return Inertia::render('Approval/AccountabilityReports/Show', ['report' => $fundAccountabilityReport->load(['submission.cooperative', 'submitter', 'items', 'attachments', 'financeReviewer', 'approver'])]);
    }

    public function approve(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('approve', $fundAccountabilityReport);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $this->service->approve($request->user(), $fundAccountabilityReport, $data['notes'] ?? null);

        return back()->with('success', 'Pertanggungjawaban disetujui dan ditutup.');
    }
}
