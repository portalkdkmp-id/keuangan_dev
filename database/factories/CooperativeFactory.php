<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Cooperative;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cooperative>
 */
class CooperativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $province = Province::factory()->create();
        $city = City::factory()->for($province)->create(['full_code' => $province->code.'.'.fake()->unique()->numerify('##')]);
        $district = District::factory()->for($city)->create(['full_code' => $city->full_code.'.'.fake()->unique()->numerify('##')]);
        $village = Village::factory()->for($district)->create(['full_code' => $district->full_code.'.'.fake()->unique()->numerify('####')]);

        return [
            'nik' => fake()->unique()->numerify('################'),
            'name' => 'KDKMP '.fake()->company(),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'is_active' => true,
        ];
    }
}
