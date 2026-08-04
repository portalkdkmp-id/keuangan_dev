<?php

namespace App\Models;

use App\Enums\AdvanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceDetail extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['advance_status' => AdvanceStatus::class, 'estimated_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'disbursed_amount' => 'decimal:2', 'expected_transaction_date' => 'date', 'expected_settlement_date' => 'date', 'disbursed_at' => 'datetime', 'settled_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'recipient_bank_account_id');
    }

    public function settlement()
    {
        return $this->hasOne(FundAccountabilityReport::class);
    }

    public function isOverdue(): bool
    {
        return $this->expected_settlement_date?->isPast() && ! in_array($this->advance_status, [AdvanceStatus::CLOSED, AdvanceStatus::REJECTED, AdvanceStatus::CANCELLED], true);
    }
}
