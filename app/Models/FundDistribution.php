<?php

namespace App\Models;

use App\Enums\FundDistributionStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundDistribution extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'submission_fund_distributions';

    protected $guarded = [];

    protected $hidden = ['destination_account_number_snapshot'];

    protected $appends = ['destination_account_number_masked'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transfer_date' => 'date', 'transferred_at' => 'datetime', 'confirmed_at' => 'datetime', 'payment_method' => PaymentMethod::class, 'status' => FundDistributionStatus::class];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function disbursement()
    {
        return $this->belongsTo(SubmissionDisbursement::class, 'submission_disbursement_id');
    }

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function recipientCooperative()
    {
        return $this->belongsTo(Cooperative::class, 'recipient_cooperative_id');
    }

    public function attachments()
    {
        return $this->hasMany(FundDistributionAttachment::class);
    }

    public function receiptConfirmation()
    {
        return $this->hasOne(FundReceiptConfirmation::class);
    }

    public function getDestinationAccountNumberMaskedAttribute(): ?string
    {
        $value = $this->destination_account_number_snapshot;

        return $value ? str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4) : null;
    }
}
