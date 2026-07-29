<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['financial_submission_id', 'category_id', 'category_name', 'description', 'quantity', 'unit', 'unit_price', 'subtotal', 'notes', 'sort_order'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function category()
    {
        return $this->belongsTo(SubmissionCategory::class);
    }
}
