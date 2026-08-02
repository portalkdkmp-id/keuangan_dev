<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementDetail extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expense_total' => 'decimal:2', 'claimed_amount' => 'decimal:2', 'finance_validated_amount' => 'decimal:2', 'approval_approved_amount' => 'decimal:2', 'director_approved_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'source_additional_amount' => 'decimal:2', 'expense_date_from' => 'date', 'expense_date_to' => 'date', 'paid_at' => 'datetime'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'claimant_bank_account_id');
    }

    public function expenses()
    {
        return $this->hasMany(ReimbursementExpense::class)->orderBy('sort_order');
    }

    public function sourceAccountability()
    {
        return $this->belongsTo(FundAccountabilityReport::class, 'source_accountability_report_id');
    }
}
