<?php

namespace App\Models;

use App\Enums\DisbursementRecipientType;
use App\Enums\DisbursementStatus;
use App\Enums\DistributionStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionDisbursement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'financial_submission_id', 'director_review_id', 'disbursement_number',
        'disbursed_by', 'amount', 'payment_method', 'bank_name',
        'source_account_name', 'source_account_number_masked',
        'destination_bank_snapshot', 'destination_account_number_snapshot',
        'destination_account_holder_snapshot', 'transaction_reference',
        'transfer_date', 'transferred_at', 'notes', 'status', 'recipient_type',
        'recipient_user_id', 'recipient_cooperative_id', 'recipient_name_snapshot',
        'source_company_bank_account_id', 'source_bank_name', 'source_account_number_snapshot',
        'source_account_holder_snapshot', 'destination_bank_account_id', 'destination_reference_type',
        'destination_reference_id', 'requires_distribution', 'distribution_status', 'received_by_recipient_at',
    ];

    protected $hidden = ['source_account_number_snapshot', 'destination_account_number_snapshot'];

    protected $appends = ['source_account_number_masked_snapshot', 'destination_account_number_masked'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'transfer_date' => 'date',
            'transferred_at' => 'datetime',
            'status' => DisbursementStatus::class,
            'recipient_type' => DisbursementRecipientType::class,
            'distribution_status' => DistributionStatus::class,
            'requires_distribution' => 'boolean',
            'received_by_recipient_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function directorReview()
    {
        return $this->belongsTo(SubmissionDirectorReview::class, 'director_review_id');
    }

    public function disburser()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function attachments()
    {
        return $this->hasMany(DisbursementAttachment::class);
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function recipientCooperative()
    {
        return $this->belongsTo(Cooperative::class, 'recipient_cooperative_id');
    }

    public function sourceCompanyBankAccount()
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    public function distributions()
    {
        return $this->hasMany(FundDistribution::class);
    }

    public function receiptConfirmation()
    {
        return $this->hasOne(FundReceiptConfirmation::class);
    }

    public function getSourceAccountNumberMaskedSnapshotAttribute(): ?string
    {
        return $this->maskAccountNumber($this->source_account_number_snapshot);
    }

    public function getDestinationAccountNumberMaskedAttribute(): ?string
    {
        return $this->maskAccountNumber($this->destination_account_number_snapshot);
    }

    private function maskAccountNumber(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4);
    }
}
