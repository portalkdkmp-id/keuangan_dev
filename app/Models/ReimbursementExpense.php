<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementExpense extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'actual_amount' => 'decimal:2'];
    }

    public function detail()
    {
        return $this->belongsTo(ReimbursementDetail::class, 'reimbursement_detail_id');
    }

    public function attachments()
    {
        return $this->hasMany(ReimbursementExpenseAttachment::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(SubmissionRequestType::class, 'expense_type_id');
    }
}
