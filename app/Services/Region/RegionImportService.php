<?php

namespace App\Services\Region;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegionImportService
{
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

        $stats = ['provinces' => $this->emptyStats(), 'cities' => $this->emptyStats(), 'districts' => $this->emptyStats(), 'villages' => $this->emptyStats()];

        try {
            DB::transaction(function () use ($payload, $truncate, $dryRun, &$stats) {
                if ($truncate && ! $dryRun) {
                    DB::table('villages')->delete();
                    DB::table('districts')->delete();
                    DB::table('cities')->delete();
                    DB::table('provinces')->delete();
                }

                $stats['provinces'] = $this->importProvinces($payload['provinsi'], $dryRun);
                $stats['cities'] = $this->importCities($payload['kabupaten'], $dryRun);
                $stats['districts'] = $this->importDistricts($payload['kecamatan'], $dryRun);
                $stats['villages'] = $this->importVillages($payload['desa'], $dryRun);

                if ($dryRun) {
                    throw new DryRunRollback($stats);
                }
            });
        } catch (DryRunRollback $rollback) {
            $stats = $rollback->stats;
        }

        $this->auditLog->record('regions.imported', 'Import wilayah dijalankan.', null, [], ['dry_run' => $dryRun, 'truncate' => $truncate, 'stats' => $stats]);

        return $stats;
    }

    private function importProvinces(array $rows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        foreach (array_chunk($rows, 1000) as $chunk) {
            foreach ($chunk as $row) {
                $code = $this->code($row['kode_provinsi'] ?? null);
                if (! $code) {
                    $this->fail($stats, 'Province tanpa kode', $row);

                    continue;
                }
                $exists = Province::where('code', $code)->exists();
                if (! $dryRun) {
                    Province::updateOrCreate(['code' => $code], ['name' => (string) ($row['nama_provinsi'] ?? ''), 'latitude' => $row['lat'] ?? null, 'longitude' => $row['long'] ?? null]);
                }
                $stats[$exists ? 'updated' : 'inserted']++;
            }
        }

        return $stats;
    }

    private function importCities(array $rows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        foreach (array_chunk($rows, 1000) as $chunk) {
            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $province = Province::where('code', explode('.', $fullCode)[0] ?? '')->first();
                if (! $fullCode || ! $province) {
                    $this->fail($stats, 'Parent province tidak ditemukan', $row);

                    continue;
                }
                $exists = City::where('full_code', $fullCode)->exists();
                if (! $dryRun) {
                    City::updateOrCreate(['full_code' => $fullCode], ['province_id' => $province->id, 'code' => $this->code($row['kode_kabupaten'] ?? null), 'name' => (string) ($row['nama_kabupaten'] ?? ''), 'latitude' => $row['lat'] ?? null, 'longitude' => $row['long'] ?? null]);
                }
                $stats[$exists ? 'updated' : 'inserted']++;
            }
        }

        return $stats;
    }

    private function importDistricts(array $rows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        foreach (array_chunk($rows, 1000) as $chunk) {
            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $cityCode = implode('.', array_slice(explode('.', $fullCode), 0, 2));
                $city = City::where('full_code', $cityCode)->first();
                if (! $fullCode || ! $city) {
                    $this->fail($stats, 'Parent city tidak ditemukan', $row);

                    continue;
                }
                $exists = District::where('full_code', $fullCode)->exists();
                if (! $dryRun) {
                    District::updateOrCreate(['full_code' => $fullCode], ['city_id' => $city->id, 'code' => $this->code($row['kode_kecamatan'] ?? null), 'name' => (string) ($row['nama_kecamatan'] ?? ''), 'latitude' => $row['lat'] ?? null, 'longitude' => $row['long'] ?? null]);
                }
                $stats[$exists ? 'updated' : 'inserted']++;
            }
        }

        return $stats;
    }

    private function importVillages(array $rows, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        foreach (array_chunk($rows, 1000) as $chunk) {
            foreach ($chunk as $row) {
                $fullCode = $this->code($row['kode_lengkap'] ?? null);
                $districtCode = implode('.', array_slice(explode('.', $fullCode), 0, 3));
                $district = District::where('full_code', $districtCode)->first();
                if (! $fullCode || ! $district) {
                    $this->fail($stats, 'Parent district tidak ditemukan', $row);

                    continue;
                }
                $exists = Village::where('full_code', $fullCode)->exists();
                if (! $dryRun) {
                    Village::updateOrCreate(['full_code' => $fullCode], ['district_id' => $district->id, 'code' => $this->code($row['kode_desa'] ?? null), 'name' => (string) ($row['nama_desa'] ?? ''), 'latitude' => $row['lat'] ?? null, 'longitude' => $row['long'] ?? null]);
                }
                $stats[$exists ? 'updated' : 'inserted']++;
            }
        }

        return $stats;
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

class DryRunRollback extends \RuntimeException
{
    public function __construct(public readonly array $stats)
    {
        parent::__construct('Dry run rollback.');
    }
}
