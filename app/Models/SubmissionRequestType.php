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

    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
