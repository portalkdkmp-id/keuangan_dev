<?php

namespace App\Http\Controllers;

use App\Http\Requests\Submission\StoreSubmissionAttachmentRequest;
use App\Models\FinancialSubmission;
use App\Models\SubmissionAttachment;
use App\Services\Submission\SubmissionAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionAttachmentController extends Controller
{
    public function __construct(private readonly SubmissionAttachmentService $attachments) {}

    public function store(StoreSubmissionAttachmentRequest $request, FinancialSubmission $financialSubmission): RedirectResponse
    {
        $this->attachments->upload($request->user(), $financialSubmission, $request->file('file'), $request->validated());

        return back()->with('success', 'Attachment berhasil diunggah.');
    }

    public function destroy(SubmissionAttachment $submissionAttachment): RedirectResponse
    {
        Gate::authorize('delete', $submissionAttachment);
        $this->attachments->delete(request()->user(), $submissionAttachment);

        return back()->with('success', 'Attachment berhasil dihapus.');
    }

    public function download(SubmissionAttachment $submissionAttachment): StreamedResponse
    {
        Gate::authorize('view', $submissionAttachment);

        return Storage::disk($submissionAttachment->disk)->download($submissionAttachment->path, $submissionAttachment->original_name);
    }

    public function preview(SubmissionAttachment $submissionAttachment): Response
    {
        Gate::authorize('view', $submissionAttachment);
        abort_unless(str_starts_with($submissionAttachment->mime_type, 'image/'), 404);

        return response(Storage::disk($submissionAttachment->disk)->get($submissionAttachment->path), 200, [
            'Content-Type' => $submissionAttachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$submissionAttachment->original_name.'"',
        ]);
    }
}
