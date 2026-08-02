<?php

namespace App\Services\Receipt;

use App\Enums\DistributionStatus;
use App\Enums\FundDistributionStatus;
use App\Models\FundDistribution;
use App\Models\FundReceiptConfirmation;
use App\Models\SubmissionDisbursement;
use App\Models\User;
use App\Notifications\FundReceivedConfirmationNotification;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FundReceiptService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function confirmDisbursement(User $actor, SubmissionDisbursement $source, array $data): FundReceiptConfirmation
    {
        return $this->confirm($actor, $source, null, $data);
    }

    public function confirmDistribution(User $actor, FundDistribution $source, array $data): FundReceiptConfirmation
    {
        return $this->confirm($actor, null, $source, $data);
    }

    private function confirm(User $actor, ?SubmissionDisbursement $disbursement, ?FundDistribution $distribution, array $data): FundReceiptConfirmation
    {
        return DB::transaction(function () use ($actor, $disbursement, $distribution, $data) {
            if ($disbursement) {
                $source = SubmissionDisbursement::whereKey($disbursement->id)->lockForUpdate()->firstOrFail();
                if ($source->requires_distribution || ! in_array($source->recipient_type?->value, ['pic_kdkmp', 'cooperative'], true) || $source->submission()->value('submitted_by') !== $actor->id) {
                    throw ValidationException::withMessages(['receipt' => 'Dana ini tidak dapat dikonfirmasi oleh user saat ini.']);
                }
                if ($source->receiptConfirmation()->exists()) {
                    throw ValidationException::withMessages(['receipt' => 'Penerimaan dana sudah pernah dikonfirmasi.']);
                }
                $attributes = ['submission_disbursement_id' => $source->id, 'fund_distribution_id' => null, 'financial_submission_id' => $source->financial_submission_id, 'amount' => $source->amount];
                $source->update(['received_by_recipient_at' => $data['received_at'], 'distribution_status' => DistributionStatus::ACCOUNTABILITY_PENDING]);
            } else {
                $source = FundDistribution::whereKey($distribution->id)->lockForUpdate()->firstOrFail();
                if ($source->submission()->value('submitted_by') !== $actor->id) {
                    throw ValidationException::withMessages(['receipt' => 'Dana ini tidak dapat dikonfirmasi oleh user saat ini.']);
                }
                if ($source->receiptConfirmation()->exists()) {
                    throw ValidationException::withMessages(['receipt' => 'Penerimaan dana sudah pernah dikonfirmasi.']);
                }
                $attributes = ['submission_disbursement_id' => null, 'fund_distribution_id' => $source->id, 'financial_submission_id' => $source->financial_submission_id, 'amount' => $source->amount];
                $source->update(['confirmed_at' => $data['received_at'], 'confirmed_by' => $actor->id, 'status' => FundDistributionStatus::RECIPIENT_CONFIRMED]);
                $source->disbursement()->update(['distribution_status' => DistributionStatus::ACCOUNTABILITY_PENDING, 'received_by_recipient_at' => $data['received_at']]);
            }
            $receipt = FundReceiptConfirmation::create([...$attributes, 'recipient_user_id' => $actor->id, 'confirmed_by' => $actor->id, 'received_at' => $data['received_at'], 'notes' => $data['notes'] ?? null, 'status' => 'confirmed']);
            $this->audit->record('fund_receipt.confirmed', 'Penerimaan dana dikonfirmasi.', $receipt, [], ['amount' => $receipt->amount, 'received_at' => $receipt->received_at]);
            DB::afterCommit(fn () => $this->notify($receipt->fresh('submission')));

            return $receipt;
        });
    }

    private function notify(FundReceiptConfirmation $receipt): void
    {
        User::role(['finance_staff', 'finance_approver', 'finance_director'])->where('is_active', true)->get()->each(fn (User $user) => $user->notify(new FundReceivedConfirmationNotification($receipt)));
    }
}
