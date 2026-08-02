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

class FinanceAccountabilityController extends Controller
{
    public function __construct(private readonly FundAccountabilityService $service) {}

    public function index(): Response
    {
        Gate::authorize('accountability-reports.review');

        return Inertia::render('Finance/AccountabilityReports/Index', ['reports' => FundAccountabilityReport::with(['submission:id,submission_number,title,cooperative_id', 'submission.cooperative:id,name', 'submitter:id,name'])->whereIn('status', [AccountabilityStatus::SUBMITTED->value, AccountabilityStatus::FINANCE_REVIEW->value, AccountabilityStatus::REVISION_REQUESTED->value, AccountabilityStatus::FINANCE_VERIFIED->value])->latest('submitted_at')->paginate(10)]);
    }

    public function show(FundAccountabilityReport $fundAccountabilityReport): Response
    {
        Gate::authorize('view', $fundAccountabilityReport);

        return Inertia::render('Finance/AccountabilityReports/Show', ['report' => $fundAccountabilityReport->load(['submission.cooperative', 'submitter', 'items', 'attachments', 'financeReviewer'])]);
    }

    public function startReview(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('review', $fundAccountabilityReport);
        $this->service->startReview($request->user(), $fundAccountabilityReport);

        return back()->with('success', 'Review laporan dimulai.');
    }

    public function requestRevision(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('review', $fundAccountabilityReport);
        $data = $request->validate(['notes' => ['required', 'string', 'max:5000']]);
        $this->service->requestRevision($request->user(), $fundAccountabilityReport, $data['notes']);

        return back()->with('success', 'Permintaan revisi dikirim.');
    }

    public function verify(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('review', $fundAccountabilityReport);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $this->service->verify($request->user(), $fundAccountabilityReport, $data['notes'] ?? null);

        return back()->with('success', 'Laporan berhasil diverifikasi.');
    }
}
