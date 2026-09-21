<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('submissions.history');

        $submissions = $this->visibleQuery($request)
            ->whereIn('status', SubmissionStatus::finalValues())
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested
                ->where('submission_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('items', fn (Builder $items) => $items->where('description', 'like', "%{$search}%"))
                ->orWhereHas('cooperative', fn (Builder $cooperative) => $cooperative->where('name', 'like', "%{$search}%"))))
            ->when($request->string('status')->toString(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->with(['cooperative:id,name', 'submitter:id,name'])
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Submissions/History/Index', [
            'submissions' => $submissions,
            'filters' => $request->only(['search', 'status']),
            'statuses' => SubmissionStatus::finalValues(),
        ]);
    }

    public function show(Request $request, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('submissions.history');
        abort_unless(in_array($financialSubmission->status->value, SubmissionStatus::finalValues(), true), 404);
        abort_unless($this->visibleQuery($request)->whereKey($financialSubmission->id)->exists(), 403);

        return Inertia::render('Submissions/History/Show', [
            'submission' => $financialSubmission->load([
                'cooperative.city.province', 'submitterCity', 'submitter', 'requestCategory',
                'requestType', 'recipientBankAccount', 'items.requestType', 'attachments',
                'financeDetail', 'revisionRequests.requester', 'revisionRequests.response.responder',
                'approvalReviews.approver', 'directorReviews.director', 'statusHistories.actor',
                'disbursement.attachments',
            ]),
        ]);
    }

    private function visibleQuery(Request $request): Builder
    {
        return FinancialSubmission::query()
            ->when($request->user()->hasRole('pic_kdkmp'), fn (Builder $query) => $query->where('submitted_by', $request->user()->id));
    }
}
