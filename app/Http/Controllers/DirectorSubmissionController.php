<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DirectorSubmissionController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('director-submissions.view');

        return Inertia::render('Director/Submissions/Index', [
            'submissions' => FinancialSubmission::query()
                ->where('status', SubmissionStatus::DIRECTOR_REVIEW)
                ->with(['cooperative.city.province', 'submitter', 'approvalDecisionMaker:id,name', 'financeValidator:id,name'])
                ->orderBy('forwarded_to_director_at')
                ->paginate(10),
        ]);
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('viewDirectorQueue', $financialSubmission);

        return Inertia::render('Director/Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'attachments', 'financeDetail', 'approvalDecisionMaker', 'approvalReviews.approver', 'statusHistories.actor']),
        ]);
    }
}
