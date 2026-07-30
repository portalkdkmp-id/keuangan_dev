<?php

namespace App\Models;

use App\Enums\RevisionRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionRevisionRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'financial_submission_id', 'requested_by', 'revision_number', 'subject',
        'message', 'fields', 'status', 'requested_at', 'responded_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'status' => RevisionRequestStatus::class,
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function response()
    {
        return $this->hasOne(SubmissionRevisionResponse::class, 'revision_request_id');
    }
}
