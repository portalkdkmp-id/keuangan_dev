<?php

namespace App\Console\Commands;

use App\Services\Cooperative\CooperativeImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cooperatives:import {file} {--province-id=} {--dry-run}')]
#[Description('Import master data koperasi dari file Excel.')]
class ImportCooperativesCommand extends Command
{
    public function handle(CooperativeImportService $service): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $result = $service->import($file, $this->option('province-id') ?: null, (bool) $this->option('dry-run'));
        $stats = $result['stats'];
        $this->line(sprintf('inserted=%d updated=%d skipped=%d failed=%d', $stats['inserted'], $stats['updated'], $stats['skipped'], $stats['failed']));

        foreach (array_slice($result['failures'], 0, 20) as $failure) {
            $this->warn(sprintf('Row %s [%s]: %s', $failure['row'], $failure['nik'] ?? '-', $failure['message']));
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
