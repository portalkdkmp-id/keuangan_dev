<?php

namespace App\Models;

use Database\Factories\UserBankAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBankAccount extends Model
{
    /** @use HasFactory<UserBankAccountFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'bank_name', 'account_number', 'account_holder_name', 'is_active', 'is_primary'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_primary' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
