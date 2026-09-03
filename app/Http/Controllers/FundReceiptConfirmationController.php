<?php

namespace App\Http\Controllers;

use App\Models\FundDistribution;
use App\Models\FundReceiptConfirmation;
use App\Models\SubmissionDisbursement;
use App\Services\Receipt\FundReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FundReceiptConfirmationController extends Controller
{
    public function __construct(private readonly FundReceiptService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FundReceiptConfirmation::class);
        $direct = SubmissionDisbursement::with(['submission:id,submission_number,title,submitted_by', 'attachments', 'receiptConfirmation'])->whereHas('submission', fn ($q) => $q->where('submitted_by', $request->user()->id))->whereIn('recipient_type', ['pic_kdkmp', 'cooperative'])->where('requires_distribution', false)->whereDoesntHave('receiptConfirmation')->get();
        $distributed = FundDistribution::with(['submission:id,submission_number,title,submitted_by', 'attachments', 'receiptConfirmation'])->whereHas('submission', fn ($q) => $q->where('submitted_by', $request->user()->id))->whereDoesntHave('receiptConfirmation')->get();
        $history = FundReceiptConfirmation::with('submission:id,submission_number,title')->where('recipient_user_id', $request->user()->id)->latest('received_at')->paginate(10);

        return Inertia::render('Pic/FundReceipts/Index', ['directDisbursements' => $direct, 'distributions' => $distributed, 'confirmations' => $history]);
    }

    public function confirmDisbursement(Request $request, SubmissionDisbursement $submissionDisbursement): RedirectResponse
    {
        Gate::authorize('fund-receipts.confirm');
        $data = $request->validate(['received_at' => ['required', 'date'], 'notes' => ['required', 'string', 'max:5000']]);
        $this->service->confirmDisbursement($request->user(), $submissionDisbursement, $data);

        return back()->with('success', 'Penerimaan dana berhasil dikonfirmasi.');
    }

    public function confirmDistribution(Request $request, FundDistribution $fundDistribution): RedirectResponse
    {
        Gate::authorize('fund-receipts.confirm');
        $data = $request->validate(['received_at' => ['required', 'date'], 'notes' => ['required', 'string', 'max:5000']]);
        $this->service->confirmDistribution($request->user(), $fundDistribution, $data);

        return back()->with('success', 'Penerimaan dana berhasil dikonfirmasi.');
    }
}
