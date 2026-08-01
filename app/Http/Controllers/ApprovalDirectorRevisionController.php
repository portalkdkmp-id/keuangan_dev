<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approval\ResubmitDirectorRequest;
use App\Models\FinancialSubmission;
use App\Services\Approval\FinanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalDirectorRevisionController extends Controller
{
    public function __construct(private readonly FinanceApprovalService $approvals) {}

    public function index(): Response
    {
        Gate::authorize('approval-submissions.view-director-revision');

        return Inertia::render('Approval/DirectorRevisions/Index', [
            'submissions' => FinancialSubmission::query()
                ->where('status', 'director_revision_requested')
                ->with(['cooperative', 'submitter', 'directorReviews.director'])
                ->latest('last_director_revision_requested_at')
                ->paginate(10),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('viewDirectorRevision', $financialSubmission);

        return Inertia::render('Approval/DirectorRevisions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitter', 'requestCategory', 'requestType', 'financeDetail', 'approvalReviews.approver', 'directorReviews.director', 'attachments', 'statusHistories.actor']),
        ]);
    }

    public function resubmit(ResubmitDirectorRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('resubmitToDirector', $financialSubmission);
        $this->approvals->resubmitToDirector($request->user(), $financialSubmission, $request->validated());

        return to_route('approval.director-revisions.index')->with('success', 'Pengajuan dikirim ulang ke Finance Director.');
    }
}
