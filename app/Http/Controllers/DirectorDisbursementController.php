<?php

namespace App\Http\Controllers;

use App\Models\DisbursementAttachment;
use App\Models\SubmissionDisbursement;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirectorDisbursementController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('disbursements.view');

        return Inertia::render('Director/Disbursements/Index', [
            'disbursements' => SubmissionDisbursement::query()
                ->with(['submission.cooperative', 'submission.submitter', 'disburser'])
                ->latest('transferred_at')
                ->paginate(10),
        ]);
    }

    public function show(SubmissionDisbursement $submissionDisbursement): Response
    {
        Gate::authorize('disbursements.view');

        return Inertia::render('Director/Disbursements/Show', [
            'disbursement' => $submissionDisbursement->load(['submission.cooperative', 'submission.submitter', 'directorReview.director', 'attachments', 'disburser']),
        ]);
    }

    public function downloadProof(DisbursementAttachment $disbursementAttachment): StreamedResponse
    {
        Gate::authorize('disbursements.download-proof');

        return Storage::disk($disbursementAttachment->disk)->download($disbursementAttachment->path, $disbursementAttachment->original_name);
    }
}
