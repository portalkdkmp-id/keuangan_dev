<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalSubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('approval-submissions.view');

        return Inertia::render('Approval/Submissions/Index', [
            'submissions' => FinancialSubmission::query()
                ->where('status', SubmissionStatus::APPROVAL_REVIEW)
                ->with(['cooperative.city.province', 'submitter', 'financeValidator:id,name'])
                ->withCount('attachments')
                ->orderBy('forwarded_to_approval_at')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Approval/Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments', 'financeDetail', 'financeValidator', 'approvalForwarder', 'revisionRequests.response', 'statusHistories.actor']),
        ]);
    }
}
