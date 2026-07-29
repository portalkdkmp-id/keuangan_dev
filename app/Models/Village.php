<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['district_id', 'code', 'full_code', 'name', 'latitude', 'longitude'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
