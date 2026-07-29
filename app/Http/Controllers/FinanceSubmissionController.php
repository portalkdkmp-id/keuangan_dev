<?php

namespace App\Http\Controllers;

use App\Models\FinancialSubmission;
use App\Services\Submission\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FinanceSubmissionController extends Controller
{
    public function __construct(private readonly SubmissionService $submissions) {}

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
            'submission' => $financialSubmission->load(['cooperative.city.province', 'submitter', 'items', 'attachments', 'statusHistories.actor']),
        ]);
    }

    public function startReview(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('review', $financialSubmission);
        $this->submissions->startFinanceReview($request->user(), $financialSubmission);

        return back()->with('success', 'Review pengajuan dimulai.');
    }
}
