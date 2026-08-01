<?php

namespace App\Models;

use App\Enums\DisbursementStatus;
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
        'transfer_date', 'transferred_at', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'transfer_date' => 'date',
            'transferred_at' => 'datetime',
            'status' => DisbursementStatus::class,
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
}
