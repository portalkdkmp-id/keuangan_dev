<?php

namespace App\Http\Controllers;

use App\Models\FundReturn;
use App\Services\FundReturn\FundReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalFundReturnController extends Controller
{
    public function __construct(private FundReturnService $service) {}

    public function index(): Response
    {
        Gate::authorize('fund-returns.approve');

        return Inertia::render('Approval/FundReturns/Index', ['returns' => FundReturn::with(['submission:id,submission_number,title', 'returner:id,name'])->whereIn('status', ['finance_verified', 'closed'])->latest()->paginate(10)]);
    }

    public function show(FundReturn $fundReturn): Response
    {
        Gate::authorize('view', $fundReturn);

        return Inertia::render('Approval/FundReturns/Show', ['fundReturn' => $fundReturn->load(['submission', 'accountabilityReport', 'returner:id,name', 'attachments'])]);
    }

    public function approve(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('approve', $fundReturn);
        $d = $r->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $this->service->approve($r->user(), $fundReturn, $d['notes'] ?? null);

        return back()->with('success', 'Pengembalian disetujui dan pertanggungjawaban ditutup.');
    }

    public function reject(Request $r, FundReturn $fundReturn): RedirectResponse
    {
        Gate::authorize('fund-returns.reject');
        $data = $r->validate(['notes' => ['required', 'string', 'max:5000']]);
        $this->service->reject($r->user(), $fundReturn, $data['notes']);

        return to_route('approval.fund-returns.index')->with('success', 'Pengembalian dana ditolak.');
    }
}
