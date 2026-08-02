<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reimbursement\SaveReimbursementRequest;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\ReimbursementExpenseAttachment;
use App\Models\SubmissionRequestType;
use App\Services\Reimbursement\ReimbursementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $service) {}

    public function index(Request $r): Response
    {
        Gate::authorize('reimbursements.view');

        return Inertia::render('Reimbursements/Index', ['submissions' => FinancialSubmission::with('cooperative:id,name')->where('submitted_by', $r->user()->id)->where('type', 'reimbursement')->latest()->paginate(10)]);
    }

    public function create(Request $r): Response
    {
        Gate::authorize('createReimbursement', FinancialSubmission::class);

        return $this->form($r);
    }

    public function store(SaveReimbursementRequest $r): RedirectResponse
    {
        Gate::authorize('createReimbursement', FinancialSubmission::class);
        $s = $this->service->createDraft($r->user(), $r->validated(), $r->file('purchase_proofs', []), $r->file('payment_proofs', []));

        return to_route('reimbursements.show', $s)->with('success', 'Draft reimbursement berhasil dibuat.');
    }

    public function show(FinancialSubmission $financialSubmission): Response
    {
        abort_unless($financialSubmission->isReimbursement(), 404);
        Gate::authorize('view', $financialSubmission);

        return Inertia::render('Reimbursements/Show', ['submission' => $this->load($financialSubmission)]);
    }

    public function edit(Request $r, FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('updateReimbursement', $financialSubmission);

        return $this->form($r, $financialSubmission);
    }

    public function update(SaveReimbursementRequest $r, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('updateReimbursement', $financialSubmission);
        $this->service->updateDraft($r->user(), $financialSubmission, $r->validated(), $r->file('purchase_proofs', []), $r->file('payment_proofs', []));

        return to_route('reimbursements.show', $financialSubmission)->with('success', 'Reimbursement berhasil diperbarui.');
    }

    public function submit(Request $r, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('submitReimbursement', $financialSubmission);
        $this->service->submit($r->user(), $financialSubmission);

        return back()->with('success', 'Reimbursement diajukan ke Finance Staff.');
    }

    public function fromAccountability(Request $r, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('createShortfallReimbursement', $fundAccountabilityReport);
        $submission = $this->service->createFromAccountabilityShortfall($r->user(), $fundAccountabilityReport);

        return to_route('reimbursements.edit', $submission)->with('success', 'Draft reimbursement selisih dibuat. Lengkapi bukti sebelum diajukan.');
    }

    public function download(ReimbursementExpenseAttachment $attachment): StreamedResponse
    {
        $submission = $attachment->expense->detail->submission;
        Gate::authorize('downloadReimbursementAttachment', $submission);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function form(Request $r, ?FinancialSubmission $s = null): Response
    {
        return Inertia::render('Reimbursements/Form', ['submission' => $s ? $this->load($s) : null, 'cooperatives' => $r->user()->hasRole('finance_staff') ? Cooperative::where('is_active', true)->orderBy('name')->get(['id', 'name']) : $r->user()->assignedCooperatives()->where('is_active', true)->orderBy('name')->get(['cooperatives.id', 'name']), 'bankAccounts' => $r->user()->bankAccounts()->where('is_active', true)->get(['id', 'bank_name', 'account_number', 'account_holder_name']), 'expenseTypes' => SubmissionRequestType::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    private function load(FinancialSubmission $s): FinancialSubmission
    {
        return $s->load(['cooperative:id,name', 'submitter:id,name', 'reimbursementDetail.expenses.attachments', 'reimbursementDetail.bankAccount']);
    }
}
