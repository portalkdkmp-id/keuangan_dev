<?php

namespace App\Http\Controllers;

use App\Http\Requests\Submission\ResubmitSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionRequest;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Services\Submission\SubmissionRevisionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionRevisionController extends Controller
{
    public function __construct(private readonly SubmissionRevisionService $revisions) {}

    public function edit(Request $request, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('resubmit', $financialSubmission);

        return Inertia::render('Submissions/Revision', [
            'submission' => $financialSubmission->load(['items', 'attachments', 'openRevisionRequest.requester']),
            'cooperatives' => $request->user()->assignedCooperatives()->orderBy('name')->get(['cooperatives.id', 'name']),
            'requestCategories' => SubmissionRequestCategory::where('is_active', true)
                ->when($request->user()->hasRole('pic_kdkmp'), fn (Builder $query) => $query->whereNot('slug', 'operasional-tim-sales'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'requestTypes' => SubmissionRequestType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => $request->user()->bankAccounts()->where('is_active', true)->orderByDesc('is_primary')->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
        ]);
    }

    public function update(UpdateSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('resubmit', $financialSubmission);
        $this->revisions->reviseSubmission($request->user(), $financialSubmission, $request->validated());

        return back()->with('success', 'Perubahan revisi berhasil disimpan.');
    }

    public function resubmit(ResubmitSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('resubmit', $financialSubmission);
        $this->revisions->resubmit($request->user(), $financialSubmission, $request->validated('message'));

        return to_route('submissions.show', $financialSubmission)->with('success', 'Pengajuan berhasil dikirim ulang.');
    }
}
