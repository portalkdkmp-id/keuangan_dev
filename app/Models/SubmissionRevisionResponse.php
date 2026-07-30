<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionRevisionResponse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['revision_request_id', 'financial_submission_id', 'responded_by', 'message', 'change_summary', 'responded_at'];

    protected function casts(): array
    {
        return ['change_summary' => 'array', 'responded_at' => 'datetime'];
    }

    public function revisionRequest()
    {
        return $this->belongsTo(SubmissionRevisionRequest::class, 'revision_request_id');
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
