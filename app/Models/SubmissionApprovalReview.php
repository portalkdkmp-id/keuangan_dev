<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalReviewStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionApprovalReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'financial_submission_id', 'approver_id', 'review_number', 'status', 'decision',
        'submitted_amount', 'approved_amount', 'notes', 'rejection_reason',
        'revision_subject', 'revision_message', 'revision_fields', 'change_summary',
        'started_at', 'decided_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalReviewStatus::class,
            'decision' => ApprovalDecision::class,
            'revision_fields' => 'array',
            'submitted_amount' => 'decimal:2',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
