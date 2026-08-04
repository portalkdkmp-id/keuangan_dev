<?php

namespace App\Services\Disbursement;

use App\Enums\DisbursementRecipientType;
use App\Models\CooperativeBankAccount;
use App\Models\FinancialSubmission;
use App\Models\UserBankAccount;
use Illuminate\Validation\ValidationException;

class DisbursementRecipientService
{
    public function resolve(FinancialSubmission $submission, array $data): array
    {
        $type = DisbursementRecipientType::from($data['recipient_type']);

        return match ($type) {
            DisbursementRecipientType::FINANCE_STAFF => $this->userRecipient($data, 'finance_staff', ! $submission->isAdvance()),
            DisbursementRecipientType::PIC_KDKMP => $this->picRecipient($submission, $data),
            DisbursementRecipientType::COOPERATIVE => $this->cooperativeRecipient($submission, $data),
            DisbursementRecipientType::OTHER => $this->otherRecipient($data),
        };
    }

    private function userRecipient(array $data, string $role, bool $requiresDistribution): array
    {
        $account = UserBankAccount::query()->with('user.roles')->whereKey($data['destination_bank_account_id'] ?? null)->where('is_active', true)->first();
        if (! $account || $account->user_id !== ($data['recipient_user_id'] ?? null) || ! $account->user->hasRole($role)) {
            throw ValidationException::withMessages(['destination_bank_account_id' => 'Rekening tujuan tidak valid untuk penerima yang dipilih.']);
        }

        return $this->snapshot($account->bank_name, $account->account_number, $account->account_holder_name, [
            'recipient_user_id' => $account->user_id,
            'recipient_cooperative_id' => null,
            'recipient_name_snapshot' => $account->user->name,
            'destination_bank_account_id' => $account->id,
            'destination_reference_type' => UserBankAccount::class,
            'destination_reference_id' => $account->id,
            'requires_distribution' => $requiresDistribution,
        ]);
    }

    private function picRecipient(FinancialSubmission $submission, array $data): array
    {
        if (($data['recipient_user_id'] ?? $submission->submitted_by) !== $submission->submitted_by) {
            throw ValidationException::withMessages(['recipient_user_id' => 'PIC penerima harus sama dengan PIC pemilik pengajuan.']);
        }
        $data['recipient_user_id'] = $submission->submitted_by;

        return $this->userRecipient($data, 'pic_kdkmp', false);
    }

    private function cooperativeRecipient(FinancialSubmission $submission, array $data): array
    {
        if (($data['recipient_cooperative_id'] ?? null) !== $submission->cooperative_id) {
            throw ValidationException::withMessages(['recipient_cooperative_id' => 'Koperasi penerima harus sama dengan koperasi pengajuan.']);
        }
        $account = CooperativeBankAccount::query()->with('cooperative')->whereKey($data['destination_bank_account_id'] ?? null)->where('cooperative_id', $submission->cooperative_id)->where('is_active', true)->first();
        if (! $account) {
            throw ValidationException::withMessages(['destination_bank_account_id' => 'Rekening koperasi tujuan tidak valid.']);
        }

        return $this->snapshot($account->bank_name, $account->account_number, $account->account_holder_name, [
            'recipient_user_id' => null,
            'recipient_cooperative_id' => $account->cooperative_id,
            'recipient_name_snapshot' => $account->cooperative->name,
            'destination_bank_account_id' => $account->id,
            'destination_reference_type' => CooperativeBankAccount::class,
            'destination_reference_id' => $account->id,
            'requires_distribution' => false,
        ]);
    }

    private function otherRecipient(array $data): array
    {
        return $this->snapshot($data['destination_bank_name'], $data['destination_account_number'], $data['destination_account_holder'], [
            'recipient_user_id' => null,
            'recipient_cooperative_id' => null,
            'recipient_name_snapshot' => $data['recipient_name'],
            'destination_bank_account_id' => null,
            'destination_reference_type' => null,
            'destination_reference_id' => null,
            'requires_distribution' => false,
        ]);
    }

    private function snapshot(string $bank, string $number, string $holder, array $extra): array
    {
        return [...$extra, 'destination_bank_snapshot' => $bank, 'destination_account_number_snapshot' => $number, 'destination_account_holder_snapshot' => $holder];
    }
}
