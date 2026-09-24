<?php

namespace App\Services\Pic;

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Cooperative\CooperativeExcelReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PicImportService
{
    public function __construct(
        private readonly CooperativeExcelReader $reader,
        private readonly AuditLogService $audit,
    ) {}

    public function import(string $path, bool $dryRun = false): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'failed' => 0];
        $failures = [];

        foreach ($this->reader->rows($path) as $index => $row) {
            try {
                DB::transaction(fn () => $this->importRow($row, $dryRun, $stats));
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $failures[] = ['row' => $index + 2, 'email' => $row['email'] ?? null, 'message' => $exception->getMessage()];
                Log::warning('PIC import row failed', ['row_number' => $index + 2, 'email' => $row['email'] ?? null, 'error' => $exception->getMessage()]);
            }
        }

        $this->audit->record('pic.imported', 'Import PIC KDKMP dijalankan.', null, [], [
            'dry_run' => $dryRun,
            'stats' => $stats,
            'failures' => array_slice($failures, 0, 20),
        ]);

        return ['stats' => $stats, 'failures' => $failures];
    }

    private function importRow(array $row, bool $dryRun, array &$stats): void
    {
        $name = $this->value($row, ['nama', 'name']);
        $email = Str::lower((string) $this->value($row, ['email']));
        $phone = $this->value($row, ['nomor_telepon', 'telepon', 'phone', 'no_hp']);
        $provinceName = $this->value($row, ['provinsi', 'province']);
        $cityName = $this->value($row, ['kota_kabupaten', 'kota/kabupaten', 'kabupaten', 'kota']);
        $password = $this->value($row, ['password', 'kata_sandi']);
        $isActive = $this->active($this->value($row, ['status_aktif', 'aktif', 'is_active']));

        if (! $name || ! $email || ! $provinceName || ! $cityName) {
            throw new RuntimeException('Kolom nama, email, provinsi, dan kota/kabupaten wajib diisi.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Format email tidak valid.');
        }

        $province = $this->matchOne(Province::all(), $provinceName, 'Provinsi tidak ditemukan atau ambigu.');
        $city = $this->matchOne(City::where('province_id', $province->id)->get(), $cityName, 'Kota/kabupaten tidak ditemukan atau ambigu.');
        $existing = User::with('roles')->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing && ! $existing->hasRole('pic_kdkmp')) {
            throw new RuntimeException('Email sudah dipakai oleh user dengan role selain PIC KDKMP.');
        }
        if (! $existing && (! $password || mb_strlen($password) < 8)) {
            throw new RuntimeException('Password minimal 8 karakter wajib untuk PIC baru.');
        }
        if ($password && mb_strlen($password) < 8) {
            throw new RuntimeException('Password minimal 8 karakter.');
        }
        if ($phone && User::where('phone', $phone)->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))->exists()) {
            throw new RuntimeException('Nomor telepon sudah digunakan user lain.');
        }

        $stats[$existing ? 'updated' : 'inserted']++;
        if ($dryRun) {
            return;
        }

        $data = ['name' => $name, 'email' => $email, 'phone' => $phone, 'city_id' => $city->id, 'is_active' => $isActive];
        if ($password) {
            $data['password'] = $password;
        }

        $pic = $existing ?? new User;
        $pic->fill($data)->save();
        $pic->syncRoles(['pic_kdkmp']);
    }

    private function matchOne($models, string $needle, string $message)
    {
        $matches = $models->filter(fn ($model) => $this->normalize($model->name) === $this->normalize($needle))->values();
        if ($matches->count() !== 1) {
            throw new RuntimeException($message.' Nilai: '.$needle);
        }

        return $matches->first();
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/\b(kabupaten|kab\.|kota administrasi|kota|provinsi)\b/u', '')->replaceMatches('/[^a-z0-9]+/u', ' ')->squish()->toString();
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

    private function active(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array(Str::lower($value), ['1', 'true', 'ya', 'yes', 'aktif'], true);
    }
}
