<?php

namespace App\Models;

use Database\Factories\SubmissionRequestTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionRequestType extends Model
{
    /** @use HasFactory<SubmissionRequestTypeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['submission_request_category_id', 'name', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function requestCategory()
    {
        return $this->belongsTo(SubmissionRequestCategory::class, 'submission_request_category_id');
    }
}
