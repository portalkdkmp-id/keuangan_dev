<?php

namespace App\Services\Disbursement;

use App\Enums\DisbursementAttachmentType;
use App\Enums\DisbursementStatus;
use App\Models\FinancialSubmission;
use App\Models\SubmissionDirectorReview;
use App\Models\SubmissionDisbursement;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DisbursementService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function createCompleted(User $actor, FinancialSubmission $submission, SubmissionDirectorReview $review, array $data, array $files): SubmissionDisbursement
    {
        if ($submission->disbursement()->exists()) {
            throw ValidationException::withMessages(['disbursement' => 'Pengajuan ini sudah pernah dicairkan.']);
        }

        if (count($files) < 1 || count($files) > 5) {
            throw ValidationException::withMessages(['attachments' => 'Bukti transfer wajib diunggah minimal 1 dan maksimal 5 file.']);
        }

        $disk = config('finance.attachment_disk', 'local');
        $storedFiles = [];

        try {
            $disbursement = DB::transaction(function () use ($actor, $submission, $review, $data, $files, $disk, &$storedFiles) {
                $disbursement = SubmissionDisbursement::create([
                    'financial_submission_id' => $submission->id,
                    'director_review_id' => $review->id,
                    'disbursement_number' => $this->numbers->generateDisbursementNumber(),
                    'disbursed_by' => $actor->id,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'bank_name' => $data['bank_name'] ?? null,
                    'source_account_name' => $data['source_account_name'] ?? null,
                    'source_account_number_masked' => $this->maskAccountNumber($data['source_account_number'] ?? null),
                    'destination_bank_snapshot' => $submission->bank_name_snapshot ?? $submission->recipientBankAccount?->bank_name ?? '-',
                    'destination_account_number_snapshot' => $submission->bank_account_number_snapshot ?? $submission->recipientBankAccount?->account_number ?? '-',
                    'destination_account_holder_snapshot' => $submission->bank_account_holder_snapshot ?? $submission->recipientBankAccount?->account_holder_name ?? '-',
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'transfer_date' => $data['transfer_date'],
                    'transferred_at' => $data['transferred_at'],
                    'notes' => $data['notes'] ?? null,
                    'status' => DisbursementStatus::COMPLETED,
                ]);

                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $storedName = (string) Str::uuid().'.'.$extension;
                    $path = 'submission-disbursements/'.$submission->id.'/'.$storedName;
                    Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
                    $storedFiles[] = [$disk, $path];

                    $disbursement->attachments()->create([
                        'uploaded_by' => $actor->id,
                        'original_name' => basename($file->getClientOriginalName()),
                        'stored_name' => $storedName,
                        'disk' => $disk,
                        'path' => $path,
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'extension' => $extension,
                        'size' => $file->getSize() ?: 0,
                        'attachment_type' => DisbursementAttachmentType::TRANSFER_PROOF,
                    ]);
                }

                $this->auditLog->record('disbursement.completed', 'Pencairan dana selesai.', $submission, [], [
                    'disbursement_id' => $disbursement->id,
                    'disbursement_number' => $disbursement->disbursement_number,
                    'amount' => $disbursement->amount,
                    'payment_method' => $data['payment_method'],
                    'source_account_number_masked' => $disbursement->source_account_number_masked,
                ]);

                return $disbursement;
            });

            return $disbursement->refresh();
        } catch (\Throwable $exception) {
            foreach ($storedFiles as [$storedDisk, $path]) {
                Storage::disk($storedDisk)->delete($path);
            }
            Log::error('Disbursement upload failed', ['submission_id' => $submission->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        $clean = preg_replace('/\s+/', '', $accountNumber) ?: $accountNumber;
        $length = strlen($clean);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', max(0, $length - 4)).substr($clean, -4);
    }
}
