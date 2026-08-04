<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundAccountabilityAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function report()
    {
        return $this->belongsTo(FundAccountabilityReport::class, 'fund_accountability_report_id');
    }

    public function item()
    {
        return $this->belongsTo(FundAccountabilityItem::class, 'fund_accountability_item_id');
    }
}
