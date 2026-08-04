<?php

namespace App\Http\Controllers;

use App\Enums\AdvanceStatus;
use App\Http\Requests\AdvanceSettlement\SaveAdvanceSettlementRequest;
use App\Models\AdvanceDetail;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestType;
use App\Services\AdvanceSettlement\AdvanceSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdvanceSettlementController extends Controller
{
    public function __construct(private readonly AdvanceSettlementService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('advance-settlements.view');

        return Inertia::render('Advances/Settlements/Index', [
            'advances' => AdvanceDetail::with(['submission:id,submission_number,title', 'settlement:id,advance_detail_id,status,realized_amount,remaining_amount,additional_amount'])
                ->where('responsible_user_id', $request->user()->id)
                ->whereIn('advance_status', collect(AdvanceStatus::cases())->reject(fn (AdvanceStatus $status) => in_array($status, [AdvanceStatus::DRAFT, AdvanceStatus::SUBMITTED, AdvanceStatus::UNDER_REVIEW, AdvanceStatus::APPROVED, AdvanceStatus::PENDING_DISBURSEMENT, AdvanceStatus::REJECTED, AdvanceStatus::CANCELLED], true))->map->value)
                ->latest('disbursed_at')->paginate(10),
        ]);
    }

    public function create(Request $request, AdvanceDetail $advanceDetail): Response
    {
        $this->authorizeOwner($request, $advanceDetail);

        return $this->form($advanceDetail, $advanceDetail->settlement);
    }

    public function store(SaveAdvanceSettlementRequest $request, AdvanceDetail $advanceDetail): RedirectResponse
    {
        $this->authorizeOwner($request, $advanceDetail);
        $report = $this->service->saveDraft($request->user(), $advanceDetail, $request->validated(), $request->file('purchase_proofs', []), $request->file('payment_proofs', []));

        return to_route('advance-settlements.show', $report)->with('success', 'Draft settlement berhasil disimpan.');
    }

    public function show(Request $request, FundAccountabilityReport $fundAccountabilityReport): Response
    {
        $this->authorizeReportOwner($request, $fundAccountabilityReport);

        return Inertia::render('Advances/Settlements/Show', ['report' => $this->load($fundAccountabilityReport)]);
    }

    public function edit(Request $request, FundAccountabilityReport $fundAccountabilityReport): Response
    {
        $this->authorizeReportOwner($request, $fundAccountabilityReport);

        return $this->form($fundAccountabilityReport->advanceDetail, $fundAccountabilityReport);
    }

    public function update(SaveAdvanceSettlementRequest $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        $this->authorizeReportOwner($request, $fundAccountabilityReport);
        $this->service->saveDraft($request->user(), $fundAccountabilityReport->advanceDetail, $request->validated(), $request->file('purchase_proofs', []), $request->file('payment_proofs', []));

        return to_route('advance-settlements.show', $fundAccountabilityReport)->with('success', 'Draft settlement berhasil diperbarui.');
    }

    public function submit(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        $this->authorizeReportOwner($request, $fundAccountabilityReport);
        $this->service->submit($request->user(), $fundAccountabilityReport);

        return back()->with('success', 'Settlement berhasil dikirim ke Finance Staff.');
    }

    private function form(AdvanceDetail $advance, ?FundAccountabilityReport $report): Response
    {
        return Inertia::render('Advances/Settlements/Form', [
            'advance' => $advance->load(['submission.cooperative:id,name', 'responsibleUser:id,name']),
            'report' => $report?->load('items.attachments'),
            'categories' => SubmissionRequestType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function load(FundAccountabilityReport $report): FundAccountabilityReport
    {
        return $report->load(['advanceDetail.submission.cooperative', 'submitter', 'items.attachments', 'financeReviewer', 'approver', 'fundReturn', 'generatedReimbursement.submission']);
    }

    private function authorizeOwner(Request $request, AdvanceDetail $advance): void
    {
        abort_unless($request->user()->can('advance-settlements.create') && $advance->responsible_user_id === $request->user()->id, 403);
    }

    private function authorizeReportOwner(Request $request, FundAccountabilityReport $report): void
    {
        abort_unless($report->source_type === 'advance' && $report->submitted_by === $request->user()->id && $request->user()->can('advance-settlements.view'), 403);
    }
}
