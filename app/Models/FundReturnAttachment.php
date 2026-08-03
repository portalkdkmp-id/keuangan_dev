<?php

namespace App\Models;

use App\Enums\FundReturnAttachmentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FundReturnAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['attachment_type' => FundReturnAttachmentType::class];
    }

    public function fundReturn()
    {
        return $this->belongsTo(FundReturn::class);
    }
}
