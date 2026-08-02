<?php

namespace App\Http\Controllers;

use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\SubmitSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionRequest;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Services\Submission\SubmissionAttachmentService;
use App\Services\Submission\SubmissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
        private readonly SubmissionAttachmentService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FinancialSubmission::class);

        return Inertia::render('Submissions/Index', [
            'submissions' => $this->submissions->paginateForPic($request->user(), $request->all()),
            'filters' => $request->only(['search', 'status', 'cooperative_id']),
            'cooperatives' => $request->user()->hasRole('finance_staff')
                ? Cooperative::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : $request->user()->assignedCooperatives()->orderBy('name')->get(['cooperatives.id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', FinancialSubmission::class);

        return Inertia::render('Submissions/Create', $this->formData($request));
    }

    public function store(StoreSubmissionRequest $request): RedirectResponse
    {
        $submission = $this->submissions->createDraft($request->user(), $request->validated());
        foreach ($request->file('attachments', []) as $file) {
            $this->attachments->upload($request->user(), $submission, $file);
        }

        if ($request->input('action') === 'submit') {
            $this->submissions->submit($request->user(), $submission);

            return to_route('submissions.show', $submission)->with('success', 'Pengajuan berhasil dikirim ke Staff Keuangan.');
        }

        return to_route('submissions.index')->with('success', 'Draft berhasil disimpan.');
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Submissions/Show', [
            'submission' => $financialSubmission->load(['cooperative', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments', 'statusHistories.actor']),
        ]);
    }

    public function edit(Request $request, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('update', $financialSubmission);

        return Inertia::render('Submissions/Edit', [
            ...$this->formData($request),
            'submission' => $financialSubmission->load(['items', 'attachments']),
        ]);
    }

    public function update(UpdateSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        $this->submissions->updateDraft($request->user(), $financialSubmission, $request->validated());

        return to_route('submissions.edit', $financialSubmission)->with('success', 'Draft berhasil disimpan.');
    }

    public function review(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('update', $financialSubmission);

        return Inertia::render('Submissions/Review', [
            'submission' => $financialSubmission->load(['cooperative', 'submitterCity', 'submitter', 'requestCategory', 'requestType', 'recipientBankAccount', 'items', 'attachments']),
        ]);
    }

    public function destroy(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('delete', $financialSubmission);
        $this->submissions->deleteDraft($request->user(), $financialSubmission);

        return to_route('submissions.index')->with('success', 'Draft pengajuan berhasil dihapus.');
    }

    public function submit(SubmitSubmissionRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        $this->submissions->submit($request->user(), $financialSubmission);

        return to_route('submissions.show', $financialSubmission)->with('success', 'Pengajuan berhasil dikirim ke Staff Keuangan.');
    }

    public function cancel(Request $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('cancel', $financialSubmission);
        $this->submissions->cancelDraft($request->user(), $financialSubmission, $request->input('reason'));

        return to_route('submissions.index')->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    private function formData(Request $request): array
    {
        return [
            'cooperatives' => $request->user()->assignedCooperatives()->orderBy('name')->get(['cooperatives.id', 'name']),
            'categories' => SubmissionCategory::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'requestCategories' => SubmissionRequestCategory::where('is_active', true)
                ->where(fn (Builder $query) => $query->whereNull('code')->orWhere('code', '!=', 'reimbursement'))
                ->when($request->user()->hasRole('pic_kdkmp'), fn (Builder $query) => $query->whereNot('slug', 'operasional-tim-sales'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'requestTypes' => SubmissionRequestType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => $request->user()->bankAccounts()->where('is_active', true)->orderByDesc('is_primary')->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
        ];
    }
}
