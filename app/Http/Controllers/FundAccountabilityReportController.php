<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accountability\SaveAccountabilityRequest;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityAttachment;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestType;
use App\Services\Accountability\FundAccountabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FundAccountabilityReportController extends Controller
{
    public function __construct(private readonly FundAccountabilityService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FundAccountabilityReport::class);
        $reports = FundAccountabilityReport::with(['submission:id,submission_number,title,cooperative_id', 'submission.cooperative:id,name'])->where('submitted_by', $request->user()->id)->latest()->paginate(10);
        $eligible = FinancialSubmission::with(['cooperative:id,name', 'receiptConfirmations'])->where('submitted_by', $request->user()->id)->whereHas('receiptConfirmations')->whereDoesntHave('accountabilityReport')->latest()->get(['id', 'submission_number', 'title', 'cooperative_id']);

        return Inertia::render('Pic/AccountabilityReports/Index', ['reports' => $reports, 'eligibleSubmissions' => $eligible]);
    }

    public function create(FinancialSubmission $financialSubmission): Response
    {
        Gate::authorize('create', [FundAccountabilityReport::class, $financialSubmission]);

        return $this->form($financialSubmission, null);
    }

    public function store(SaveAccountabilityRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        Gate::authorize('create', [FundAccountabilityReport::class, $financialSubmission]);
        $report = $this->service->create($request->user(), $financialSubmission, $request->validated(), $request->file('attachments', []));

        return to_route('accountability-reports.show', $report)->with('success', 'Draft pertanggungjawaban berhasil dibuat.');
    }

    public function show(FundAccountabilityReport $fundAccountabilityReport): Response
    {
        Gate::authorize('view', $fundAccountabilityReport);

        return Inertia::render('Pic/AccountabilityReports/Show', ['report' => $this->load($fundAccountabilityReport)]);
    }

    public function edit(FundAccountabilityReport $fundAccountabilityReport): Response
    {
        Gate::authorize('update', $fundAccountabilityReport);

        return $this->form($fundAccountabilityReport->submission, $fundAccountabilityReport);
    }

    public function update(SaveAccountabilityRequest $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('update', $fundAccountabilityReport);
        $this->service->update($request->user(), $fundAccountabilityReport, $request->validated(), $request->file('attachments', []));

        return to_route('accountability-reports.show', $fundAccountabilityReport)->with('success', 'Pertanggungjawaban berhasil diperbarui.');
    }

    public function submit(Request $request, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('submit', $fundAccountabilityReport);
        $this->service->submit($request->user(), $fundAccountabilityReport);

        return back()->with('success', 'Laporan berhasil dikirim ke Finance Staff.');
    }

    public function download(FundAccountabilityAttachment $attachment): StreamedResponse
    {
        Gate::authorize('downloadAttachment', $attachment->report);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function preview(FundAccountabilityAttachment $attachment): SymfonyResponse
    {
        Gate::authorize('downloadAttachment', $attachment->report);
        abort_unless(str_starts_with($attachment->mime_type, 'image/'), 404);

        return response(Storage::disk($attachment->disk)->get($attachment->path), 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
        ]);
    }

    private function form(FinancialSubmission $submission, ?FundAccountabilityReport $report): Response
    {
        return Inertia::render('Pic/AccountabilityReports/Form', ['submission' => $submission->load(['cooperative:id,name', 'items:id,financial_submission_id,request_type_id,description,subtotal,sort_order']), 'report' => $report?->load(['items', 'attachments']), 'receivedAmount' => $submission->receiptConfirmations()->sum('amount'), 'categories' => SubmissionRequestType::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    private function load(FundAccountabilityReport $report): FundAccountabilityReport
    {
        return $report->load(['submission.cooperative', 'submission.disbursement', 'submitter', 'items', 'attachments', 'financeReviewer', 'approver']);
    }
}
