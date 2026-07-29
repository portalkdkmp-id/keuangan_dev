<?php

namespace App\Services\Cooperative;

use App\Models\City;
use App\Models\Cooperative;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CooperativeImportService
{
    public function __construct(
        private readonly CooperativeExcelReader $reader,
        private readonly AuditLogService $auditLog,
    ) {}

    public function import(string $path, ?string $defaultProvinceId = null, bool $dryRun = false): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $failures = [];
        $rows = $this->reader->rows($path);
        $defaultProvince = $defaultProvinceId ? Province::find($defaultProvinceId) : null;

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::transaction(function () use ($chunk, $defaultProvince, $dryRun, &$stats, &$failures): void {
                foreach ($chunk as $index => $row) {
                    try {
                        $this->importRow($row, $defaultProvince, $dryRun, $stats);
                    } catch (\Throwable $exception) {
                        $stats['failed']++;
                        $failures[] = ['row' => $index + 2, 'nik' => $row['nik'] ?? null, 'message' => $exception->getMessage()];
                        Log::warning('Cooperative import row failed', ['row' => $row, 'error' => $exception->getMessage()]);
                    }
                }
            });
        }

        $this->auditLog->record('cooperative.imported', 'Import koperasi dijalankan.', null, [], [
            'dry_run' => $dryRun,
            'stats' => $stats,
            'failures' => array_slice($failures, 0, 20),
        ]);

        return ['stats' => $stats, 'failures' => $failures];
    }

    private function importRow(array $row, ?Province $defaultProvince, bool $dryRun, array &$stats): void
    {
        $nik = $this->value($row, ['nik']);
        $name = $this->value($row, ['nama', 'name']);
        $cityName = $this->value($row, ['kota_kabupaten', 'kota/kabupaten', 'kabupaten', 'kota']);
        $districtName = $this->value($row, ['kecamatan']);
        $villageName = $this->value($row, ['desa', 'kelurahan']);

        if (! $nik || ! $name || ! $cityName || ! $districtName || ! $villageName) {
            throw new \RuntimeException('Kolom wajib NIK, nama, desa, kecamatan, dan Kota/Kabupaten harus terisi.');
        }

        $province = $this->resolveProvince($row, $defaultProvince, $cityName);
        $city = $this->resolveCity($province, $cityName, $districtName);
        $district = $this->matchOne(
            District::where('city_id', $city->id)->get(),
            $districtName,
            'Kecamatan tidak ditemukan atau ambigu.'
        );
        $village = $this->matchOne(
            Village::where('district_id', $district->id)->get(),
            $villageName,
            'Desa tidak ditemukan atau ambigu.'
        );

        $exists = Cooperative::where('nik', $nik)->exists();
        $stats[$exists ? 'updated' : 'inserted']++;

        if ($dryRun) {
            return;
        }

        Cooperative::updateOrCreate(
            ['nik' => $nik],
            [
                'name' => $name,
                'province_id' => $province->id,
                'city_id' => $city->id,
                'district_id' => $district->id,
                'village_id' => $village->id,
                'latitude' => $this->decimal($this->value($row, ['latitude', 'lat'])),
                'longitude' => $this->decimal($this->value($row, ['longitude', 'long', 'lng'])),
                'is_active' => true,
            ]
        );
    }

    private function resolveProvince(array $row, ?Province $defaultProvince, string $cityName): Province
    {
        $provinceName = $this->value($row, ['provinsi', 'province']);
        if ($provinceName) {
            return $this->matchOne(Province::all(), $provinceName, 'Provinsi tidak ditemukan atau ambigu.');
        }

        if ($defaultProvince) {
            return $defaultProvince;
        }

        $matchedCities = City::with('province')->get()->filter(fn (City $city) => $this->matches($city->name, $cityName));
        $provinceIds = $matchedCities->pluck('province_id')->unique()->values();

        if ($provinceIds->count() !== 1) {
            throw new \RuntimeException('Provinsi tidak dapat diinfer dari kota/kabupaten. Pilih provinsi default saat import atau tambahkan kolom provinsi.');
        }

        return $matchedCities->first()->province;
    }

    private function resolveCity(Province $province, string $cityName, string $districtName): City
    {
        $cities = City::where('province_id', $province->id)->get();
        $strictMatches = $cities->filter(fn (City $city) => $this->normalizeStrict($city->name) === $this->normalizeStrict($cityName))->values();
        $matches = $strictMatches->isNotEmpty()
            ? $strictMatches
            : $cities->filter(fn (City $city) => $this->matches($city->name, $cityName))->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1 && Str::of($cityName)->lower()->contains('kabupaten')) {
            $kabupaten = $matches->filter(fn (City $city) => (int) explode('.', $city->full_code)[1] < 70)->values();
            if ($kabupaten->count() === 1) {
                return $kabupaten->first();
            }
        }

        if ($matches->count() > 1 && Str::of($cityName)->lower()->contains('kota')) {
            $kota = $matches->filter(fn (City $city) => (int) explode('.', $city->full_code)[1] >= 70)->values();
            if ($kota->count() === 1) {
                return $kota->first();
            }
        }

        if ($matches->count() > 1) {
            $withDistrict = $matches
                ->filter(fn (City $city) => District::where('city_id', $city->id)->get()->contains(fn (District $district) => $this->matches($district->name, $districtName)))
                ->values();

            if ($withDistrict->count() === 1) {
                return $withDistrict->first();
            }
        }

        throw new \RuntimeException('Kota/kabupaten tidak ditemukan atau ambigu. Nilai: '.$cityName);
    }

    private function matchOne($models, string $needle, string $message)
    {
        $strictMatches = $models->filter(fn ($model) => $this->normalizeStrict($model->name) === $this->normalizeStrict($needle))->values();
        $matches = $strictMatches->isNotEmpty()
            ? $strictMatches
            : $models->filter(fn ($model) => $this->matches($model->name, $needle))->values();

        if ($matches->count() !== 1) {
            throw new \RuntimeException($message.' Nilai: '.$needle);
        }

        return $matches->first();
    }

    private function matches(string $databaseName, string $input): bool
    {
        return $this->normalize($databaseName) === $this->normalize($input);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\b(kabupaten|kab\.|kota administrasi|kota|provinsi)\b/u', '')
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->squish()
            ->toString();
    }

    private function normalizeStrict(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\bkab\./u', 'kabupaten')
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->squish()
            ->toString();
    }

    private function value(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $normalized = Str::of($key)->lower()->replace(['/', '-'], ' ')->squish()->replace(' ', '_')->toString();
            if (($row[$normalized] ?? '') !== '') {
                return trim((string) $row[$normalized]);
            }
        }

        return null;
    }

    private function decimal(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) str_replace(',', '.', $value), 7, '.', '');
    }
}
