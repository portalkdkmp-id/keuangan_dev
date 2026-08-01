<?php

namespace App\Models;

use App\Enums\DisbursementAttachmentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_disbursement_id', 'uploaded_by', 'original_name', 'stored_name',
        'disk', 'path', 'mime_type', 'extension', 'size', 'attachment_type',
    ];

    protected function casts(): array
    {
        return [
            'attachment_type' => DisbursementAttachmentType::class,
        ];
    }

    public function disbursement()
    {
        return $this->belongsTo(SubmissionDisbursement::class, 'submission_disbursement_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
