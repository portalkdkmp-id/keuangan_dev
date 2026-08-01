<?php

namespace App\Services\Disbursement;

use App\Enums\DisbursementAttachmentType;
use App\Enums\DisbursementStatus;
use App\Enums\DistributionStatus;
use App\Models\CompanyBankAccount;
use App\Models\FinancialSubmission;
use App\Models\SubmissionDirectorReview;
use App\Models\SubmissionDisbursement;
use App\Models\User;
use App\Notifications\SubmissionDisbursedNotification;
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
        private readonly DisbursementRecipientService $recipients,
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
                $source = CompanyBankAccount::query()->whereKey($data['source_company_bank_account_id'])->where('is_active', true)->lockForUpdate()->first();
                if (! $source) {
                    throw ValidationException::withMessages(['source_company_bank_account_id' => 'Rekening perusahaan tidak aktif atau tidak ditemukan.']);
                }
                $recipient = $this->recipients->resolve($submission, $data);
                $disbursement = SubmissionDisbursement::create([
                    'financial_submission_id' => $submission->id,
                    'director_review_id' => $review->id,
                    'disbursement_number' => $this->numbers->generateDisbursementNumber(),
                    'disbursed_by' => $actor->id,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'bank_name' => $source->bank_name,
                    'source_account_name' => $source->account_holder_name,
                    'source_account_number_masked' => $this->maskAccountNumber($source->account_number),
                    'source_company_bank_account_id' => $source->id,
                    'source_bank_name' => $source->bank_name,
                    'source_account_number_snapshot' => $source->account_number,
                    'source_account_holder_snapshot' => $source->account_holder_name,
                    'recipient_type' => $data['recipient_type'],
                    ...$recipient,
                    'distribution_status' => $recipient['requires_distribution'] ? DistributionStatus::PENDING : DistributionStatus::NOT_REQUIRED,
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
                    'source_account_number_snapshot' => $disbursement->source_account_number_snapshot,
                    'destination_account_number_snapshot' => $disbursement->destination_account_number_snapshot,
                    'recipient_type' => $disbursement->recipient_type->value,
                ]);

                DB::afterCommit(fn () => $this->notifyStakeholders($disbursement->fresh(['submission.submitter', 'recipientUser'])));

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

    private function notifyStakeholders(SubmissionDisbursement $disbursement): void
    {
        $users = User::role('finance_approver')->where('is_active', true)->get();
        $users->push($disbursement->submission->submitter);
        if ($disbursement->recipientUser) {
            $users->push($disbursement->recipientUser);
        }
        $users->filter()->unique('id')->each(fn (User $user) => $user->notify(new SubmissionDisbursedNotification($disbursement)));
    }
}
