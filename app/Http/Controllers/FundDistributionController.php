<?php

namespace App\Http\Controllers;

use App\Enums\DistributionStatus;
use App\Http\Requests\Distribution\StoreFundDistributionRequest;
use App\Models\FundDistribution;
use App\Models\FundDistributionAttachment;
use App\Models\SubmissionDisbursement;
use App\Services\Distribution\FundDistributionCalculator;
use App\Services\Distribution\FundDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FundDistributionController extends Controller
{
    public function __construct(private readonly FundDistributionService $service, private readonly FundDistributionCalculator $calculator) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FundDistribution::class);
        $user = $request->user();
        $pending = SubmissionDisbursement::query()->with(['submission:id,submission_number,title,submitted_by,cooperative_id', 'submission.submitter:id,name', 'submission.cooperative:id,name'])->where('requires_distribution', true)->whereIn('distribution_status', [DistributionStatus::PENDING->value, DistributionStatus::PARTIALLY_DISTRIBUTED->value])->when(! $user->can('fund-distributions.monitor'), fn ($q) => $q->where('recipient_user_id', $user->id))->withSum(['distributions as distributed_amount' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'amount')->latest('transferred_at')->paginate(10, ['*'], 'pending_page');
        $history = FundDistribution::query()->with(['submission:id,submission_number,title', 'recipientUser:id,name', 'recipientCooperative:id,name', 'distributor:id,name'])->when(! $user->can('fund-distributions.monitor'), fn ($q) => $q->where(fn ($scope) => $scope->where('distributed_by', $user->id)->orWhere('recipient_user_id', $user->id)))->latest('transferred_at')->paginate(10, ['*'], 'history_page');

        return Inertia::render('Finance/FundDistributions/Index', ['pendingDisbursements' => $pending, 'distributions' => $history]);
    }

    public function create(Request $request, SubmissionDisbursement $submissionDisbursement): Response
    {
        Gate::authorize('create', [FundDistribution::class, $submissionDisbursement]);
        $submissionDisbursement->load(['submission.submitter.bankAccounts' => fn ($q) => $q->where('is_active', true), 'submission.cooperative.bankAccounts' => fn ($q) => $q->where('is_active', true), 'distributions']);
        $distributed = $submissionDisbursement->distributions->where('status.value', '!=', 'cancelled')->sum(fn ($item) => (float) $item->amount);

        return Inertia::render('Finance/FundDistributions/Create', ['disbursement' => $submissionDisbursement, 'remainingAmount' => $this->calculator->remaining($submissionDisbursement->amount, $distributed)]);
    }

    public function store(StoreFundDistributionRequest $request, SubmissionDisbursement $submissionDisbursement): RedirectResponse
    {
        Gate::authorize('create', [FundDistribution::class, $submissionDisbursement]);
        $distribution = $this->service->create($request->user(), $submissionDisbursement, $request->validated(), $request->file('attachments', []));

        return to_route('finance.fund-distributions.show', $distribution)->with('success', 'Distribusi dana berhasil dicatat.');
    }

    public function show(FundDistribution $fundDistribution): Response
    {
        Gate::authorize('view', $fundDistribution);

        return Inertia::render('Finance/FundDistributions/Show', ['distribution' => $fundDistribution->load(['submission.cooperative', 'submission.submitter', 'disbursement.attachments', 'distributor', 'recipientUser', 'recipientCooperative', 'attachments', 'receiptConfirmation'])]);
    }

    public function downloadProof(FundDistributionAttachment $attachment): StreamedResponse
    {
        Gate::authorize('downloadProof', $attachment->distribution);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
