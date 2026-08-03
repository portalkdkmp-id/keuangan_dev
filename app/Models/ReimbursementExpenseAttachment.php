<?php

namespace App\Models;

use App\Enums\ReimbursementAttachmentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReimbursementExpenseAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['attachment_type' => ReimbursementAttachmentType::class];
    }

    public function expense()
    {
        return $this->belongsTo(ReimbursementExpense::class, 'reimbursement_expense_id');
    }
}
