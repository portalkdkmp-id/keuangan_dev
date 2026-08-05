<?php

namespace App\Http\Controllers;

use App\Http\Requests\Advance\SaveAdvanceRequest;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Services\Advance\AdvanceService;
use App\Services\Submission\SubmissionAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdvanceController extends Controller
{
    public function __construct(private AdvanceService $service, private SubmissionAttachmentService $attachments) {}

    public function index(Request $r): Response
    {
        Gate::authorize('advances.view');

        return Inertia::render('Advances/Index', ['advances' => FinancialSubmission::with(['cooperative:id,name', 'advanceDetail'])->where('type', 'advance')->where(fn ($q) => $r->user()->hasRole('super_admin') ? $q : $q->where('submitted_by', $r->user()->id))->latest()->paginate(10)]);
    }

    public function create(Request $r): Response
    {
        Gate::authorize('advances.create');

        return $this->form($r);
    }

    public function store(SaveAdvanceRequest $r): RedirectResponse
    {
        Gate::authorize('advances.create');
        $submission = $this->service->createDraft($r->user(), $r->validated());
        foreach ($r->file('attachments', []) as $file) {
            $this->attachments->upload($r->user(), $submission, $file);
        }

        return to_route('advances.show', $submission)->with('success', 'Draft uang panjar dibuat.');
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        abort_unless($financialSubmission->isAdvance(), 404);
        Gate::authorize('view', $financialSubmission->advanceDetail);

        return Inertia::render('Advances/Show', ['submission' => $this->load($financialSubmission)]);
    }

    public function edit(Request $r, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('update', $financialSubmission->advanceDetail);

        return $this->form($r, $financialSubmission);
    }

    public function update(SaveAdvanceRequest $r, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('update', $financialSubmission->advanceDetail);
        $this->service->updateDraft($r->user(), $financialSubmission, $r->validated());
        foreach ($r->file('attachments', []) as $file) {
            $this->attachments->upload($r->user(), $financialSubmission, $file);
        }

        return to_route('advances.show', $financialSubmission)->with('success', 'Draft uang panjar diperbarui.');
    }

    public function submit(Request $r, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('submit', $financialSubmission->advanceDetail);
        $this->service->submit($r->user(), $financialSubmission);

        return back()->with('success', 'Uang panjar diajukan ke Finance Staff.');
    }

    private function form(Request $r, ?FinancialSubmission $submission = null): Response
    {
        return Inertia::render('Advances/Form', ['submission' => $submission ? $this->load($submission) : null, 'canSubmitInternal' => $r->user()->hasAnyRole(['super_admin', 'finance_staff']), 'cooperatives' => Cooperative::where('is_active', true)->orderBy('name')->get(['id', 'name']), 'bankAccounts' => $r->user()->bankAccounts()->where('is_active', true)->orderByDesc('is_primary')->get(['id', 'bank_name', 'account_number', 'account_holder_name']), 'defaultSettlementDays' => config('finance.advance.default_settlement_days', 14)]);
    }

    private function load(FinancialSubmission $s): FinancialSubmission
    {
        return $s->load(['cooperative:id,name', 'advanceDetail.responsibleUser:id,name', 'advanceDetail.bankAccount', 'attachments', 'statusHistories.actor']);
    }
}
