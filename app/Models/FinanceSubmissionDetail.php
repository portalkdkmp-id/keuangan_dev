<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceSubmissionDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'financial_submission_id', 'budget_account_code', 'budget_account_name',
        'cost_center_code', 'cost_center_name', 'expense_group', 'payment_method',
        'beneficiary_name', 'beneficiary_bank', 'beneficiary_account_number',
        'beneficiary_account_holder', 'tax_applicable', 'tax_notes', 'finance_notes',
        'validated_total_amount', 'staff_reviewed_at', 'rejection_reason', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'tax_applicable' => 'boolean',
            'validated_total_amount' => 'decimal:2',
            'staff_reviewed_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
