<?php

namespace App\Models;

use Database\Factories\SubmissionRequestCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionRequestCategory extends Model
{
    /** @use HasFactory<SubmissionRequestCategoryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'slug', 'code', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
