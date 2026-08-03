<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundReturn\SaveFundReturnRequest;
use App\Models\CompanyBankAccount;
use App\Models\FundAccountabilityReport;
use App\Models\FundReturn;
use App\Models\FundReturnAttachment;
use App\Services\FundReturn\FundReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FundReturnController extends Controller
{
    public function __construct(private FundReturnService $service) {}

    public function index(Request $r): Response
    {
        Gate::authorize('viewAny', FundReturn::class);

        return Inertia::render('FundReturns/Index', ['returns' => FundReturn::with('submission:id,submission_number,title')->where('returned_by', $r->user()->id)->latest()->paginate(10), 'eligible' => FundAccountabilityReport::with('submission:id,submission_number,title')->where('submitted_by', $r->user()->id)->where('status', 'return_pending')->whereDoesntHave('fundReturn')->get()]);
    }

    public function create(Request $r, FundAccountabilityReport $fundAccountabilityReport): Response
    {
        Gate::authorize('createFundReturn', $fundAccountabilityReport);

        return $this->form($r, $fundAccountabilityReport);
    }

    public function store(SaveFundReturnRequest $r, FundAccountabilityReport $fundAccountabilityReport): RedirectResponse
    {
        Gate::authorize('createFundReturn', $fundAccountabilityReport);
        $return = $this->service->createDraft($r->user(), $fundAccountabilityReport, $r->validated(), $r->file('proof'));

        return to_route('fund-returns.show', $return)->with('success', 'Draft pengembalian dana dibuat.');
    }

    public function show(FundReturn $fundReturn): Response
    {
        Gate::authorize('view', $fundReturn);

        return Inertia::render('FundReturns/Show', ['fundReturn' => $this->load($fundReturn)]);
    }

    public function edit(Request $r, FundReturn $fundReturn): Response
    {
        Gate::authorize('update', $fundReturn);

        return $this->form($r, $fundReturn->accountabilityReport, $fundReturn);
    }

    public function update(SaveFundReturnRequest $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('update', $fundReturn);
        $this->service->updateDraft($r->user(), $fundReturn, $r->validated(), $r->file('proof'));

        return to_route('fund-returns.show', $fundReturn)->with('success', 'Pengembalian dana diperbarui.');
    }

    public function submit(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('submit', $fundReturn);
        $this->service->submit($r->user(), $fundReturn);

        return back()->with('success', 'Pengembalian diajukan untuk verifikasi.');
    }

    public function download(FundReturnAttachment $attachment): StreamedResponse
    {
        Gate::authorize('downloadAttachment', $attachment->fundReturn);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function form(Request $r, FundAccountabilityReport $report, ?FundReturn $return = null): Response
    {
        return Inertia::render('FundReturns/Form', ['report' => $report->load('submission:id,submission_number,title'), 'fundReturn' => $return?->load('attachments'), 'bankAccounts' => $r->user()->bankAccounts()->where('is_active', true)->get(['id', 'bank_name', 'account_number', 'account_holder_name']), 'companyAccounts' => CompanyBankAccount::where('is_active', true)->orderByDesc('is_primary')->get(['id', 'bank_name', 'account_number', 'account_holder_name'])]);
    }

    private function load(FundReturn $r): FundReturn
    {
        return $r->load(['submission:id,submission_number,title', 'accountabilityReport', 'returner:id,name', 'attachments']);
    }
}
