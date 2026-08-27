<?php

namespace App\Http\Controllers;

use App\Models\FundReturn;
use App\Services\Export\FundReturnExcelExportService;
use App\Services\FundReturn\FundReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceFundReturnController extends Controller
{
    public function __construct(private FundReturnService $service) {}

    public function index(): Response
    {
        Gate::authorize('fund-returns.review');

        return Inertia::render('Finance/FundReturns/Index', [
            'returns' => FundReturn::with(['submission:id,submission_number,title', 'returner:id,name'])->whereIn('status', ['submitted', 'finance_review', 'revision_requested', 'finance_verified'])->latest()->paginate(10),
            'detailBasePath' => '/finance/fund-returns',
            'exportUrl' => route('finance.fund-returns.export'),
        ]);
    }

    public function export(FundReturnExcelExportService $export): BinaryFileResponse
    {
        Gate::authorize('fund-returns.review');

        return response()->download(
            $export->generate(),
            'pengembalian-dana-'.now()->format('Ymd-His').'.xlsx',
        )->deleteFileAfterSend(true);
    }

    public function show(FundReturn $fundReturn): Response
    {
        Gate::authorize('view', $fundReturn);

        return Inertia::render('Finance/FundReturns/Show', ['fundReturn' => $fundReturn->load(['submission', 'accountabilityReport', 'returner:id,name', 'attachments'])]);
    }

    public function startReview(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('review', $fundReturn);
        $this->service->startReview($r->user(), $fundReturn);

        return back()->with('success', 'Review dimulai.');
    }

    public function revision(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('review', $fundReturn);
        $d = $r->validate(['notes' => ['required', 'string', 'max:5000']]);
        $this->service->requestRevision($r->user(), $fundReturn, $d['notes']);

        return back()->with('success', 'Revisi diminta.');
    }

    public function verify(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('verify', $fundReturn);
        $d = $r->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $this->service->verify($r->user(), $fundReturn, $d['notes'] ?? null);

        return back()->with('success', 'Pengembalian disetujui staff dan diajukan ke Finance Approval.');
    }

    public function reject(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('fund-returns.reject');
        $data = $r->validate(['notes' => ['required', 'string', 'max:5000']]);
        $this->service->reject($r->user(), $fundReturn, $data['notes']);

        return to_route('finance.fund-returns.index')->with('success', 'Pengembalian dana ditolak.');
    }
}
