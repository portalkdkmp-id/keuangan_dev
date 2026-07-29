<?php

namespace App\Console\Commands;

use App\Services\Region\RegionImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('regions:import {file} {--truncate} {--dry-run}')]
#[Description('Import master data wilayah Indonesia dari file JSON.')]
class ImportRegionsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RegionImportService $service): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $truncate = (bool) $this->option('truncate');
        if ($truncate && app()->environment('production') && ! $this->confirm('Anda yakin ingin truncate data wilayah di production?')) {
            $this->warn('Import dibatalkan.');

            return self::FAILURE;
        }

        try {
            $stats = $service->import($file, $truncate, (bool) $this->option('dry-run'));
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->first());

            return self::FAILURE;
        }

        foreach ($stats as $group => $values) {
            $this->line(sprintf('%s: inserted=%d updated=%d skipped=%d failed=%d', $group, $values['inserted'], $values['updated'], $values['skipped'], $values['failed']));
        }

        return self::SUCCESS;
    }
}
