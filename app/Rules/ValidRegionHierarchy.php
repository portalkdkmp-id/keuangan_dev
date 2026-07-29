<?php

namespace App\Rules;

use App\Models\City;
use App\Models\District;
use App\Models\Village;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRegionHierarchy implements ValidationRule
{
    public function __construct(private readonly array $data) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $city = City::find($this->data['city_id'] ?? null);
        $district = District::find($this->data['district_id'] ?? null);
        $village = Village::find($this->data['village_id'] ?? null);

        if (! $city || $city->province_id !== ($this->data['province_id'] ?? null)) {
            $fail('Kabupaten/kota tidak sesuai dengan provinsi.');
        }

        if (! $district || $district->city_id !== ($this->data['city_id'] ?? null)) {
            $fail('Kecamatan tidak sesuai dengan kabupaten/kota.');
        }

        if (! $village || $village->district_id !== ($this->data['district_id'] ?? null)) {
            $fail('Desa tidak sesuai dengan kecamatan.');
        }
    }
}
