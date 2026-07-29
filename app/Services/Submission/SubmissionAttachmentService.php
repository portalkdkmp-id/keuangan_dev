<?php

namespace App\Services\Submission;

use App\Enums\SubmissionAttachmentType;
use App\Models\FinancialSubmission;
use App\Models\SubmissionAttachment;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionAttachmentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function upload(User $user, FinancialSubmission $submission, UploadedFile $file, array $metadata = []): SubmissionAttachment
    {
        if (! $submission->canBeEditedBy($user)) {
            throw ValidationException::withMessages(['file' => 'Attachment hanya dapat ditambahkan pada draft milik Anda.']);
        }

        if ($submission->attachments()->count() >= 10) {
            throw ValidationException::withMessages(['file' => 'Maksimal 10 attachment per pengajuan.']);
        }

        $disk = config('finance.attachment_disk', 'local');
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = (string) Str::uuid().'.'.$extension;
        $path = 'finance-submissions/'.$submission->id.'/'.$storedName;
        $stored = false;

        try {
            Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
            $stored = true;

            return DB::transaction(function () use ($user, $submission, $file, $metadata, $disk, $extension, $storedName, $path) {
                $attachment = SubmissionAttachment::create([
                    'financial_submission_id' => $submission->id,
                    'uploaded_by' => $user->id,
                    'original_name' => basename($file->getClientOriginalName()),
                    'stored_name' => $storedName,
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'extension' => $extension,
                    'size' => $file->getSize() ?: 0,
                    'attachment_type' => $metadata['attachment_type'] ?? SubmissionAttachmentType::SUPPORTING_DOCUMENT,
                    'description' => $metadata['description'] ?? null,
                ]);

                $this->auditLog->record('submission.attachment_uploaded', 'Attachment pengajuan diunggah.', $submission, [], ['attachment_id' => $attachment->id]);

                return $attachment;
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                Storage::disk($disk)->delete($path);
            }
            Log::error('Submission attachment upload failed', ['error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function delete(User $user, SubmissionAttachment $attachment): void
    {
        $submission = $attachment->submission;
        if (! $submission->canBeEditedBy($user)) {
            throw ValidationException::withMessages(['attachment' => 'Attachment tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($attachment, $submission) {
            $attachment->delete();
            $this->auditLog->record('submission.attachment_deleted', 'Attachment pengajuan dihapus.', $submission, ['attachment_id' => $attachment->id]);
        });

        try {
            Storage::disk($attachment->disk)->delete($attachment->path);
        } catch (\Throwable $exception) {
            Log::error('Failed deleting submission attachment file', ['attachment_id' => $attachment->id, 'error' => $exception->getMessage()]);
        }
    }
}
