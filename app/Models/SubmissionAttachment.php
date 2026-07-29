<?php

namespace App\Models;

use App\Enums\SubmissionAttachmentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['financial_submission_id', 'uploaded_by', 'original_name', 'stored_name', 'disk', 'path', 'mime_type', 'extension', 'size', 'attachment_type', 'description'];

    protected function casts(): array
    {
        return ['attachment_type' => SubmissionAttachmentType::class];
    }

    public function submission()
    {
        return $this->belongsTo(FinancialSubmission::class, 'financial_submission_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
