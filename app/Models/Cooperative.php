<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cooperative extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nik', 'name', 'province_id', 'city_id', 'district_id', 'village_id', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function pics()
    {
        return $this->belongsToMany(User::class, 'cooperative_user_assignments')
            ->withPivot(['assigned_by', 'assigned_at', 'is_primary'])
            ->withTimestamps();
    }

    public function primaryPic()
    {
        return $this->pics()->wherePivot('is_primary', true);
    }

    public function financialSubmissions()
    {
        return $this->hasMany(FinancialSubmission::class);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin') || $user->hasAnyRole(['finance_staff', 'finance_approver', 'finance_director'])) {
            return $query;
        }

        if ($user->hasRole('pic_kdkmp')) {
            return $query->whereHas('pics', fn (Builder $picQuery) => $picQuery->whereKey($user->id));
        }

        return $query->whereRaw('1 = 0');
    }
}
