<?php

namespace App\Models;

use App\Enums\AccountabilityStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundAccountabilityReport extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => AccountabilityStatus::class, 'received_amount' => 'decimal:2', 'realized_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2', 'additional_amount' => 'decimal:2', 'usage_date_from' => 'date', 'usage_date_to' => 'date', 'submitted_at' => 'datetime', 'finance_reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function items()
    {
        return $this->hasMany(FundAccountabilityItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(FundAccountabilityAttachment::class);
    }

    public function financeReviewer()
    {
        return $this->belongsTo(User::class, 'finance_reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fundReturn()
    {
        return $this->hasOne(FundReturn::class);
    }

    public function generatedReimbursement()
    {
        return $this->hasOne(ReimbursementDetail::class, 'source_accountability_report_id');
    }

    public function advanceDetail()
    {
        return $this->belongsTo(AdvanceDetail::class);
    }
}
