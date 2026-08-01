<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Director\ApproveAndDisburseRequest;
use App\Http\Requests\Director\ApprovePendingDisbursementRequest;
use App\Http\Requests\Director\DisburseSubmissionRequest;
use App\Http\Requests\Director\RejectSubmissionRequest;
use App\Http\Requests\Director\RequestDirectorRevisionRequest;
use App\Http\Requests\Director\StartDirectorReviewRequest;
use App\Models\FinancialSubmission;
use App\Services\Director\DirectorSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DirectorSubmissionController extends Controller
{
    public function __construct(private readonly DirectorSubmissionService $directors) {}

    public function index(Request $request): Response
    {
        Gate::authorize('director-submissions.view');
        $status = $request->input('status');

        return Inertia::render('Director/Submissions/Index', [
            'submissions' => FinancialSubmission::query()
                ->whereIn('status', [
                    SubmissionStatus::DIRECTOR_REVIEW,
                    SubmissionStatus::DIRECTOR_IN_REVIEW,
                    SubmissionStatus::DIRECTOR_REVISION_REQUESTED,
                    SubmissionStatus::PENDING_DISBURSEMENT,
                    SubmissionStatus::FUND_DISBURSED,
                    SubmissionStatus::DIRECTOR_REJECTED,
                ])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['cooperative.city.province', 'submitter', 'requestCategory', 'requestType', 'approvalDecisionMaker:id,name', 'financeValidator:id,name', 'directorReviewer:id,name', 'directorReviews.director'])
                ->orderBy('forwarded_to_director_at')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Request $request, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Director/Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'attachments', 'financeDetail', 'approvalDecisionMaker', 'approvalReviews.approver', 'directorReviews.director', 'disbursement.attachments', 'disburser', 'statusHistories.actor']),
            'sourceBankAccounts' => $request->user()->bankAccounts()->where('is_active', true)->orderByDesc('is_primary')->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
        ]);
    }

    public function startReview(StartDirectorReviewRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('startDirectorReview', $financialSubmission);
        $submission = $this->directors->startReview($request->user(), $financialSubmission);

        return to_route('director.submissions.show', $submission)->with('success', 'Director review dimulai.');
    }

    public function approvePendingDisbursement(ApprovePendingDisbursementRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('approvePendingDisbursement', $financialSubmission);
        $this->directors->approvePendingDisbursement($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Pengajuan disetujui dan masuk antrean pencairan.');
    }

    public function approveAndDisburse(ApproveAndDisburseRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('approveAndDisburse', $financialSubmission);
        $this->directors->approveAndDisburse($request->user(), $financialSubmission, $request->validated(), $request->file('attachments', []));

        return back()->with('success', 'Pengajuan disetujui dan dana berhasil dikirim.');
    }

    public function disburse(DisburseSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('disburse', $financialSubmission);
        $this->directors->disburseApprovedSubmission($request->user(), $financialSubmission, $request->validated(), $request->file('attachments', []));

        return back()->with('success', 'Dana berhasil dikirim.');
    }

    public function reject(RejectSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('rejectByDirector', $financialSubmission);
        $this->directors->reject($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Pengajuan ditolak Finance Director.');
    }

    public function requestRevision(RequestDirectorRevisionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('requestDirectorRevision', $financialSubmission);
        $this->directors->requestRevision($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Revisi kepada Finance Approver berhasil dikirim.');
    }
}
