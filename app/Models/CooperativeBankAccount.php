<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CooperativeBankAccount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['cooperative_id', 'bank_name', 'account_number', 'account_holder_name', 'is_active', 'is_primary'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_primary' => 'boolean'];
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }
}
