<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\FundReceiptConfirmation;
use App\Models\SubmissionDisbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('dashboard.view');

        $user = $request->user();
        $ownedSubmissionIds = FinancialSubmission::query()->where('submitted_by', $user->id)->select('id');

        return Inertia::render('Dashboard/Index', [
            'cooperativesCount' => Cooperative::query()->accessibleBy($request->user())->count(),
            'submissionStats' => [
                'draft' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'draft')->count(),
                'waiting_finance' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'submitted')->count(),
                'finance_process' => FinancialSubmission::query()->ownedBy($request->user())->whereIn('status', ['finance_review', 'finance_validated'])->count(),
                'approval_process' => FinancialSubmission::query()->ownedBy($request->user())->whereIn('status', ['approval_review', 'approval_in_review', 'approval_revision_requested'])->count(),
                'director_process' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'director_review')->count(),
                'needs_revision' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'revision_requested')->count(),
                'approval_rejected' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'approval_rejected')->count(),
                'cancelled' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'cancelled')->count(),
                'finance_new' => FinancialSubmission::query()->where('status', 'submitted')->count(),
                'finance_review' => FinancialSubmission::query()->where('status', 'finance_review')->count(),
            ],
            'fundStats' => $user->hasRole('pic_kdkmp') ? [
                'Dana Menunggu Konfirmasi' => SubmissionDisbursement::whereIn('financial_submission_id', clone $ownedSubmissionIds)->whereDoesntHave('receiptConfirmation')->where('requires_distribution', false)->count(),
                'Dana Sudah Diterima' => FundReceiptConfirmation::whereIn('financial_submission_id', clone $ownedSubmissionIds)->sum('amount'),
                'Laporan Draft' => FundAccountabilityReport::where('submitted_by', $user->id)->where('status', 'draft')->count(),
                'Laporan Perlu Revisi' => FundAccountabilityReport::where('submitted_by', $user->id)->where('status', 'revision_requested')->count(),
                'Laporan Sedang Direview' => FundAccountabilityReport::where('submitted_by', $user->id)->whereIn('status', ['submitted', 'finance_review', 'finance_verified'])->count(),
                'Laporan Approved' => FundAccountabilityReport::where('submitted_by', $user->id)->where('status', 'closed')->count(),
                'Sisa Dana' => FundAccountabilityReport::where('submitted_by', $user->id)->sum('remaining_amount'),
                'Kekurangan Dana' => FundAccountabilityReport::where('submitted_by', $user->id)->sum('additional_amount'),
            ] : [],
        ]);
    }
}
