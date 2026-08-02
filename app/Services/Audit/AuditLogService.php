<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public function record(string $event, string $description, ?Model $subject = null, array $oldValues = [], array $newValues = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'event' => $event,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'old_values' => $this->clean($oldValues) ?: null,
                'new_values' => $this->clean($newValues) ?: null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed writing audit log', ['event' => $event, 'error' => $exception->getMessage()]);
        }
    }

    private function clean(array $values): array
    {
        $values = Arr::except($values, ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->clean($value);
            } elseif (is_string($value) && str_contains(strtolower((string) $key), 'account_number')) {
                $values[$key] = $this->maskAccountNumber($value);
            }
        }

        return $values;
    }

    private function maskAccountNumber(string $value): string
    {
        $clean = preg_replace('/\s+/', '', $value) ?: $value;

        return str_repeat('*', max(strlen($clean) - 4, 0)).substr($clean, -4);
    }
}
