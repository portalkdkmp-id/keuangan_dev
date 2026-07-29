<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionStatusHistory extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['financial_submission_id', 'from_status', 'to_status', 'changed_by', 'action', 'notes', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['from_status' => SubmissionStatus::class, 'to_status' => SubmissionStatus::class, 'metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
