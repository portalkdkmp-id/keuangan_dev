<?php

namespace App\Models;

use App\Enums\FundReturnStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundReturn extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => FundReturnStatus::class, 'expected_amount' => 'decimal:2', 'returned_amount' => 'decimal:2', 'transfer_date' => 'date', 'transferred_at' => 'datetime', 'submitted_at' => 'datetime', 'verified_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function accountabilityReport()
    {
        return $this->belongsTo(FundAccountabilityReport::class, 'fund_accountability_report_id');
    }

    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function sourceBankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'source_user_bank_account_id');
    }

    public function destinationBankAccount()
    {
        return $this->belongsTo(CompanyBankAccount::class, 'destination_company_bank_account_id');
    }

    public function attachments()
    {
        return $this->hasMany(FundReturnAttachment::class);
    }
}
