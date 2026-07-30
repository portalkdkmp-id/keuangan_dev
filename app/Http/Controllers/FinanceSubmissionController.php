<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinanceSubmission\RequestRevisionRequest;
use App\Http\Requests\FinanceSubmission\UpdateFinanceDetailRequest;
use App\Models\FinancialSubmission;
use App\Services\FinanceSubmission\FinanceSubmissionService;
use App\Services\Submission\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FinanceSubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
        private readonly FinanceSubmissionService $financeSubmissions,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('finance-submissions.view');

        return Inertia::render('Finance/Submissions/Index', [
            'submissions' => $this->submissions->paginateFinanceQueue($request->all()),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Finance/Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments', 'financeDetail', 'revisionRequests.requester', 'revisionRequests.response', 'statusHistories.actor']),
        ]);
    }

    public function startReview(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('review', $financialSubmission);
        $this->submissions->startFinanceReview($request->user(), $financialSubmission);

        return back()->with('success', 'Review pengajuan dimulai.');
    }

    public function updateFinanceDetail(UpdateFinanceDetailRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('updateFinance', $financialSubmission);
        $this->financeSubmissions->updateFinanceDetail($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Detail keuangan berhasil disimpan.');
    }

    public function requestRevision(RequestRevisionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('requestRevision', $financialSubmission);
        $this->financeSubmissions->requestRevision($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Permintaan revisi berhasil dikirim.');
    }

    public function validateSubmission(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('validateFinance', $financialSubmission);
        $this->financeSubmissions->validateSubmission($request->user(), $financialSubmission);

        return back()->with('success', 'Pengajuan berhasil divalidasi.');
    }

    public function forwardToApproval(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('forwardApproval', $financialSubmission);
        $this->financeSubmissions->forwardToApproval($request->user(), $financialSubmission);

        return back()->with('success', 'Pengajuan berhasil diteruskan ke Approval Keuangan.');
    }
}
