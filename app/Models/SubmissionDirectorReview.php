<?php

namespace App\Models;

use App\Enums\DirectorDecision;
use App\Enums\DirectorReviewStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionDirectorReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'financial_submission_id', 'director_id', 'review_number', 'status', 'decision',
        'approved_amount', 'notes', 'rejection_reason', 'revision_subject',
        'revision_message', 'revision_fields', 'change_summary', 'started_at',
        'decided_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DirectorReviewStatus::class,
            'decision' => DirectorDecision::class,
            'revision_fields' => 'array',
            'approved_amount' => 'decimal:2',
            'started_at' => 'datetime',
            'decided_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function disbursement()
    {
        return $this->hasOne(SubmissionDisbursement::class, 'director_review_id');
    }
}
