<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Approval\ApproveSubmissionRequest;
use App\Http\Requests\Approval\RejectSubmissionRequest;
use App\Http\Requests\Approval\RequestApprovalRevisionRequest;
use App\Models\FinancialSubmission;
use App\Services\Approval\FinanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalSubmissionController extends Controller
{
    public function __construct(private readonly FinanceApprovalService $approvals) {}

    public function index(Request $request): Response
    {
        Gate::authorize('approval-submissions.view');
        $status = $request->input('status');

        return Inertia::render('Approval/Submissions/Index', [
            'submissions' => FinancialSubmission::query()
                ->whereIn('status', [
                    SubmissionStatus::APPROVAL_REVIEW,
                    SubmissionStatus::APPROVAL_IN_REVIEW,
                    SubmissionStatus::APPROVAL_REVISION_REQUESTED,
                    SubmissionStatus::APPROVAL_REJECTED,
                    SubmissionStatus::DIRECTOR_REVIEW,
                ])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['cooperative.city.province', 'submitter', 'financeValidator:id,name', 'approvalReviewer:id,name', 'latestApprovalReview.approver'])
                ->orderByRaw("case when status = 'approval_review' then 0 when status = 'approval_in_review' then 1 else 2 end")
                ->orderBy('forwarded_to_approval_at')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Approval/Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments', 'financeDetail', 'financeValidator', 'approvalForwarder', 'approvalReviewer', 'approvalDecisionMaker', 'approvalReviews.approver', 'revisionRequests.response', 'statusHistories.actor']),
        ]);
    }

    public function startReview(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('startApprovalReview', $financialSubmission);
        $this->approvals->startReview($request->user(), $financialSubmission);

        return back()->with('success', 'Approval review dimulai.');
    }

    public function approve(ApproveSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('approve', $financialSubmission);
        $this->approvals->approve($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Pengajuan disetujui dan diteruskan ke Finance Director.');
    }

    public function reject(RejectSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('rejectApproval', $financialSubmission);
        $this->approvals->reject($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Pengajuan ditolak oleh Finance Approver.');
    }

    public function requestRevision(RequestApprovalRevisionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('requestApprovalRevision', $financialSubmission);
        $this->approvals->requestRevision($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Revisi kepada Finance Staff berhasil dikirim.');
    }
}
