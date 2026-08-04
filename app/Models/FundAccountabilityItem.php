<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundAccountabilityItem extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function report()
    {
        return $this->belongsTo(FundAccountabilityReport::class, 'fund_accountability_report_id');
    }

    public function attachments()
    {
        return $this->hasMany(FundAccountabilityAttachment::class);
    }
}
