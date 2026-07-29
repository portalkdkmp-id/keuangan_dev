<?php

namespace App\Services\Region;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegionImportService
{
    private const CHUNK_SIZE = 1000;

    public function __construct(private readonly AuditLogService $auditLog) {}

    public function import(string $path, bool $truncate = false, bool $dryRun = false): array
    {
        $payload = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['file' => 'JSON tidak valid: '.json_last_error_msg()]);
        }

        foreach (['provinsi', 'kabupaten', 'kecamatan', 'desa'] as $key) {
            if (! array_key_exists($key, $payload) || ! is_array($payload[$key])) {
                throw ValidationException::withMessages(['file' => "Key {$key} wajib tersedia dan berupa array."]);
            }
        }

        if ($truncate && ! $dryRun) {
            DB::transaction(function (): void {
                DB::table('villages')->delete();
                DB::table('districts')->delete();
                DB::table('cities')->delete();
                DB::table('provinces')->delete();
            });
        }

        $stats = [
            'provinces' => $this->importProvinces($payload['provinsi'], $dryRun),
            'cities' => $this->importCities($payload['kabupaten'], $payload['provinsi'], $dryRun),
            'districts' => $this->importDistricts($payload['kecamatan'], $payload['kabupaten'], $dryRun),
            'villages' => $this->importVillages($payload['desa'], $payload['kecamatan'], $dryRun),
        ];

        $this->auditLog->record('regions.imported', 'Import wilayah dijalankan.', null, [], ['dry_run' => $dryRun, 'truncate' => $truncate, 'stats' => $stats]);

        return $stats;
    }

    private function importProvinces(array $rows, bool $dryRun): array
    {
        $stats = $this->emptyStats();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $codes = collect($chunk)->map(fn (array $row) => $this->code($row['kode_provinsi'] ?? null))->filter()->values();
            $existing = Province::whereIn('code', $codes)->pluck('code')->all();
            $existing = array_flip($existing);
            $upserts = [];

            foreach ($chunk as $row) {
                $code = $this->code($row['kode_provinsi'] ?? null);
                if (! $code) {
                    $this->fail($stats, 'Province tanpa kode', $row);

                    continue;
                }

                $stats[isset($existing[$code]) ? 'updated' : 'inserted']++;
                $upserts[] = [
                    'id' => (string) Str::orderedUuid(),
                    'code' => $code,
                    'name' => (string) ($row['nama_provinsi'] ?? ''),
                    'latitude' => $row['lat'] ?? null,
                    'longitude' => $row['long'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunk(Province::class, $upserts, ['code'], ['name', 'latitude', 'longitude', 'updated_at'], $dryRun);
        }

        return $stats;
    }

    private function importCities(array $rows, array $provinceRows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        $provinceCodesFromFile = collect($provinceRows)->map(fn (array $row) => $this->code($row['kode_provinsi'] ?? null))->filter()->flip();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $provinceCodes = collect($chunk)->map(fn (array $row) => explode('.', $this->code($row['kode_lengkap'] ?? null))[0] ?? null)->filter()->unique();
            $provinceIds = Province::whereIn('code', $provinceCodes)->pluck('id', 'code');
            $fullCodes = collect($chunk)->map(fn (array $row) => $this->code($row['kode_lengkap'] ?? null))->filter();
            $existing = array_flip(City::whereIn('full_code', $fullCodes)->pluck('full_code')->all());
            $upserts = [];

            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $provinceCode = explode('.', $fullCode)[0] ?? '';
                $provinceId = $provinceIds[$provinceCode] ?? null;

                if (! $fullCode || (! $provinceId && (! $dryRun || ! isset($provinceCodesFromFile[$provinceCode])))) {
                    $this->fail($stats, 'Parent province tidak ditemukan', $row);

                    continue;
                }

                if (! $provinceId && $dryRun) {
                    $stats['inserted']++;

                    continue;
                }

                $stats[isset($existing[$fullCode]) ? 'updated' : 'inserted']++;
                $upserts[] = [
                    'id' => (string) Str::orderedUuid(),
                    'province_id' => $provinceId,
                    'code' => $this->code($row['kode_kabupaten'] ?? null),
                    'full_code' => $fullCode,
                    'name' => (string) ($row['nama_kabupaten'] ?? ''),
                    'latitude' => $row['lat'] ?? null,
                    'longitude' => $row['long'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunk(City::class, $upserts, ['full_code'], ['province_id', 'code', 'name', 'latitude', 'longitude', 'updated_at'], $dryRun);
        }

        return $stats;
    }

    private function importDistricts(array $rows, array $cityRows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        $cityCodesFromFile = collect($cityRows)->map(fn (array $row) => $this->code($row['kode_lengkap'] ?? null))->filter()->flip();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $cityCodes = collect($chunk)->map(fn (array $row) => implode('.', array_slice(explode('.', $this->code($row['kode_lengkap'] ?? null)), 0, 2)))->filter()->unique();
            $cityIds = City::whereIn('full_code', $cityCodes)->pluck('id', 'full_code');
            $fullCodes = collect($chunk)->map(fn (array $row) => $this->code($row['kode_lengkap'] ?? null))->filter();
            $existing = array_flip(District::whereIn('full_code', $fullCodes)->pluck('full_code')->all());
            $upserts = [];

            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $cityCode = implode('.', array_slice(explode('.', $fullCode), 0, 2));
                $cityId = $cityIds[$cityCode] ?? null;

                if (! $fullCode || (! $cityId && (! $dryRun || ! isset($cityCodesFromFile[$cityCode])))) {
                    $this->fail($stats, 'Parent city tidak ditemukan', $row);

                    continue;
                }

                if (! $cityId && $dryRun) {
                    $stats['inserted']++;

                    continue;
                }

                $stats[isset($existing[$fullCode]) ? 'updated' : 'inserted']++;
                $upserts[] = [
                    'id' => (string) Str::orderedUuid(),
                    'city_id' => $cityId,
                    'code' => $this->code($row['kode_kecamatan'] ?? null),
                    'full_code' => $fullCode,
                    'name' => (string) ($row['nama_kecamatan'] ?? ''),
                    'latitude' => $row['lat'] ?? null,
                    'longitude' => $row['long'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunk(District::class, $upserts, ['full_code'], ['city_id', 'code', 'name', 'latitude', 'longitude', 'updated_at'], $dryRun);
        }

        return $stats;
    }

    private function importVillages(array $rows, array $districtRows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        $districtCodesFromFile = collect($districtRows)->map(fn (array $row) => $this->code($row['kode_lengkap'] ?? null))->filter()->flip();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $districtCodes = collect($chunk)->map(fn (array $row) => implode('.', array_slice(explode('.', $this->code($row['kode_lengkap'] ?? null)), 0, 3)))->filter()->unique();
            $districtIds = District::whereIn('full_code', $districtCodes)->pluck('id', 'full_code');
            $fullCodes = collect($chunk)->map(fn (array $row) => $this->code($row['kode_lengkap'] ?? null))->filter();
            $existing = array_flip(Village::whereIn('full_code', $fullCodes)->pluck('full_code')->all());
            $upserts = [];

            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $districtCode = implode('.', array_slice(explode('.', $fullCode), 0, 3));
                $districtId = $districtIds[$districtCode] ?? null;

                if (! $fullCode || (! $districtId && (! $dryRun || ! isset($districtCodesFromFile[$districtCode])))) {
                    $this->fail($stats, 'Parent district tidak ditemukan', $row);

                    continue;
                }

                if (! $districtId && $dryRun) {
                    $stats['inserted']++;

                    continue;
                }

                $stats[isset($existing[$fullCode]) ? 'updated' : 'inserted']++;
                $upserts[] = [
                    'id' => (string) Str::orderedUuid(),
                    'district_id' => $districtId,
                    'code' => $this->code($row['kode_desa'] ?? null),
                    'full_code' => $fullCode,
                    'name' => (string) ($row['nama_desa'] ?? ''),
                    'latitude' => $row['lat'] ?? null,
                    'longitude' => $row['long'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunk(Village::class, $upserts, ['full_code'], ['district_id', 'code', 'name', 'latitude', 'longitude', 'updated_at'], $dryRun);
        }

        return $stats;
    }

    private function upsertChunk(string $modelClass, array $rows, array $uniqueBy, array $updateColumns, bool $dryRun): void
    {
        if ($dryRun || $rows === []) {
            return;
        }

        DB::transaction(fn () => $modelClass::query()->upsert($rows, $uniqueBy, $updateColumns));
    }

    private function code(mixed $value): string
    {
        return trim((string) $value);
    }

    private function emptyStats(): array
    {
        return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    }

    private function fail(array &$stats, string $message, array $row): void
    {
        $stats['failed']++;
        Log::warning('Region import row failed', ['message' => $message, 'row' => $row]);
    }
}
