<?php

namespace App\Services\Distribution;

use App\Enums\DistributionStatus;
use App\Enums\FundDistributionStatus;
use App\Models\CooperativeBankAccount;
use App\Models\FundDistribution;
use App\Models\SubmissionDisbursement;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\FundDistributionCreatedNotification;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundDistributionService
{
    public function __construct(private readonly DocumentNumberService $numbers, private readonly FundDistributionCalculator $calculator, private readonly AuditLogService $audit) {}

    /** @param array<int, UploadedFile> $files */
    public function create(User $actor, SubmissionDisbursement $source, array $data, array $files): FundDistribution
    {
        $stored = [];
        try {
            return DB::transaction(function () use ($actor, $source, $data, $files, &$stored) {
                $locked = SubmissionDisbursement::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();
                $existing = FundDistribution::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ($existing->submission_disbursement_id !== $locked->id || $existing->distributed_by !== $actor->id) {
                        throw ValidationException::withMessages(['idempotency_key' => 'Kunci request sudah digunakan untuk distribusi lain.']);
                    }

                    return $existing;
                }
                if (! $locked->requires_distribution || $locked->recipient_user_id !== $actor->id || ! in_array($locked->distribution_status, [DistributionStatus::PENDING, DistributionStatus::PARTIALLY_DISTRIBUTED], true)) {
                    throw ValidationException::withMessages(['distribution' => 'Pencairan ini tidak dapat didistribusikan oleh user saat ini.']);
                }
                $distributed = FundDistribution::where('submission_disbursement_id', $locked->id)->where('status', '!=', FundDistributionStatus::CANCELLED->value)->sum('amount');
                $remaining = $this->calculator->remaining($locked->amount, $distributed);
                if ($this->calculator->compare($data['amount'], $remaining) > 0) {
                    throw ValidationException::withMessages(['amount' => 'Nominal distribusi melebihi sisa dana.']);
                }
                $recipient = $this->recipient($locked, $data);
                $distribution = FundDistribution::create([
                    'financial_submission_id' => $locked->financial_submission_id,
                    'submission_disbursement_id' => $locked->id,
                    'distribution_number' => $this->numbers->generateDistributionNumber(),
                    'idempotency_key' => $data['idempotency_key'],
                    'distributed_by' => $actor->id,
                    'recipient_type' => $data['recipient_type'],
                    ...$recipient,
                    'amount' => $data['amount'], 'transfer_date' => $data['transfer_date'], 'transferred_at' => $data['transferred_at'],
                    'transaction_reference' => $data['transaction_reference'] ?? null, 'payment_method' => $data['payment_method'], 'notes' => $data['notes'] ?? null,
                    'status' => FundDistributionStatus::COMPLETED,
                ]);
                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $name = Str::uuid().'.'.$extension;
                    $path = "fund-distributions/{$distribution->id}/{$name}";
                    $disk = config('finance.attachment_disk', 'local');
                    Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
                    $stored[] = [$disk, $path];
                    $distribution->attachments()->create(['uploaded_by' => $actor->id, 'original_name' => basename($file->getClientOriginalName()), 'stored_name' => $name, 'disk' => $disk, 'path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'extension' => $extension, 'size' => $file->getSize() ?: 0, 'attachment_type' => 'transfer_proof']);
                }
                $newTotal = number_format((float) $distributed + (float) $data['amount'], 2, '.', '');
                $locked->update(['distribution_status' => $this->calculator->compare($newTotal, $locked->amount) === 0 ? DistributionStatus::FULLY_DISTRIBUTED : DistributionStatus::PARTIALLY_DISTRIBUTED]);
                $this->audit->record('fund_distribution.created', 'Distribusi dana dibuat.', $distribution, [], ['distribution_number' => $distribution->distribution_number, 'amount' => $distribution->amount, 'destination_account_number_snapshot' => $distribution->destination_account_number_snapshot]);
                DB::afterCommit(fn () => $this->notify($distribution->fresh(['submission.submitter'])));

                return $distribution->refresh();
            });
        } catch (\Throwable $e) {
            foreach ($stored as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            } throw $e;
        }
    }

    private function recipient(SubmissionDisbursement $source, array $data): array
    {
        if ($data['recipient_type'] === 'pic_kdkmp') {
            $userId = $source->submission()->value('submitted_by');
            $account = UserBankAccount::whereKey($data['destination_bank_account_id'] ?? null)->where('user_id', $userId)->where('is_active', true)->first();
            if (! $account) {
                throw ValidationException::withMessages(['destination_bank_account_id' => 'Rekening PIC tidak valid.']);
            }

            return ['recipient_user_id' => $userId, 'recipient_cooperative_id' => null, 'recipient_name_snapshot' => $account->user()->value('name'), 'destination_bank_name_snapshot' => $account->bank_name, 'destination_account_number_snapshot' => $account->account_number, 'destination_account_holder_snapshot' => $account->account_holder_name, 'destination_reference_type' => UserBankAccount::class, 'destination_reference_id' => $account->id];
        }
        if ($data['recipient_type'] === 'cooperative') {
            $cooperativeId = $source->submission()->value('cooperative_id');
            $account = CooperativeBankAccount::whereKey($data['destination_bank_account_id'] ?? null)->where('cooperative_id', $cooperativeId)->where('is_active', true)->first();
            if (! $account) {
                throw ValidationException::withMessages(['destination_bank_account_id' => 'Rekening koperasi tidak valid.']);
            }

            return ['recipient_user_id' => null, 'recipient_cooperative_id' => $cooperativeId, 'recipient_name_snapshot' => $account->cooperative()->value('name'), 'destination_bank_name_snapshot' => $account->bank_name, 'destination_account_number_snapshot' => $account->account_number, 'destination_account_holder_snapshot' => $account->account_holder_name, 'destination_reference_type' => CooperativeBankAccount::class, 'destination_reference_id' => $account->id];
        }
        foreach (['recipient_name', 'destination_bank_name', 'destination_account_number', 'destination_account_holder', 'notes'] as $field) {
            if (blank($data[$field] ?? null)) {
                throw ValidationException::withMessages([$field => 'Field ini wajib untuk penerima lain.']);
            }
        }

        return ['recipient_user_id' => null, 'recipient_cooperative_id' => null, 'recipient_name_snapshot' => $data['recipient_name'], 'destination_bank_name_snapshot' => $data['destination_bank_name'], 'destination_account_number_snapshot' => $data['destination_account_number'], 'destination_account_holder_snapshot' => $data['destination_account_holder'], 'destination_reference_type' => null, 'destination_reference_id' => null];
    }

    private function notify(FundDistribution $distribution): void
    {
        $users = collect([$distribution->submission->submitter])->merge(User::role('finance_approver')->where('is_active', true)->get());
        $users->filter()->unique('id')->each(fn (User $user) => $user->notify(new FundDistributionCreatedNotification($distribution)));
    }
}
