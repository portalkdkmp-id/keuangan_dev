<?php

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;

function writeRegionsJson(array $overrides = []): string
{
    $payload = array_replace_recursive([
        'provinsi' => [['kode_provinsi' => '11', 'nama_provinsi' => 'Aceh', 'lat' => 5.1, 'long' => 95.1]],
        'kabupaten' => [['kode_kabupaten' => '01', 'kode_lengkap' => '11.01', 'nama_kabupaten' => 'Aceh Selatan', 'id_provinsi' => '11']],
        'kecamatan' => [['kode_kecamatan' => '01', 'kode_lengkap' => '11.01.01', 'nama_kecamatan' => 'Bakongan', 'id_kabupaten' => '01']],
        'desa' => [['kode_desa' => '2001', 'kode_lengkap' => '11.01.01.2001', 'nama_desa' => 'Keude Bakongan', 'id_kecamatan' => '01']],
    ], $overrides);
    $path = tempnam(sys_get_temp_dir(), 'regions').'.json';
    file_put_contents($path, json_encode($payload));

    return $path;
}

test('regions import creates hierarchy and is idempotent', function () {
    $path = writeRegionsJson();

    $this->artisan('regions:import', ['file' => $path])->assertSuccessful();
    $this->artisan('regions:import', ['file' => $path])->assertSuccessful();

    expect(Province::count())->toBe(1)->and(City::count())->toBe(1)->and(District::count())->toBe(1)->and(Village::count())->toBe(1);
    expect(Village::first()->district->city->province->code)->toBe('11');
});

test('regions dry run and invalid parent handling', function () {
    $this->artisan('regions:import', ['file' => writeRegionsJson(), '--dry-run' => true])->assertSuccessful();
    expect(Province::count())->toBe(0);

    $path = writeRegionsJson(['kabupaten' => [['kode_kabupaten' => '01', 'kode_lengkap' => '99.01', 'nama_kabupaten' => 'Missing']]]);
    $this->artisan('regions:import', ['file' => $path])->expectsOutputToContain('cities: inserted=0 updated=0 skipped=0 failed=1')->assertSuccessful();
});
