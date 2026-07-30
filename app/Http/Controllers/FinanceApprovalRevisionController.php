<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\FinanceSubmission\ResubmitApprovalRequest;
use App\Http\Requests\FinanceSubmission\UpdateFinanceDetailRequest;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Services\Approval\FinanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FinanceApprovalRevisionController extends Controller
{
    public function __construct(private readonly FinanceApprovalService $approvals) {}

    public function index(): Response
    {
        Gate::authorize('finance-submissions.view-approval-revision');

        return Inertia::render('Finance/ApprovalRevisions/Index', [
            'submissions' => FinancialSubmission::query()
                ->where('status', SubmissionStatus::APPROVAL_REVISION_REQUESTED)
                ->with(['cooperative', 'submitter', 'latestApprovalReview.approver'])
                ->latest('last_approval_revision_requested_at')
                ->paginate(10),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('viewApprovalRevision', $financialSubmission);

        return Inertia::render('Finance/ApprovalRevisions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments', 'financeDetail', 'approvalReviews.approver', 'statusHistories.actor']),
            'requestCategories' => SubmissionRequestCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'requestTypes' => SubmissionRequestType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateFinanceDetailRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('updateApprovalRevision', $financialSubmission);
        $this->approvals->updateApprovalRevision($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Perbaikan approval berhasil disimpan.');
    }

    public function resubmit(ResubmitApprovalRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('resubmitToApproval', $financialSubmission);
        $this->approvals->resubmitToApproval($request->user(), $financialSubmission, $request->validated());

        return to_route('finance.approval-revisions.index')->with('success', 'Pengajuan dikirim ulang ke Finance Approval.');
    }
}
