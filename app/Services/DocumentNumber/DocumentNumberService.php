<?php

namespace App\Services\DocumentNumber;

use App\Models\DocumentSequence;

class DocumentNumberService
{
    public function generateFundRequestNumber(?\DateTimeInterface $date = null): string
    {
        $date ??= now();
        $period = $date->format('Y/m');

        $sequence = DocumentSequence::query()
            ->where('document_type', 'FR')
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            $sequence = DocumentSequence::create([
                'document_type' => 'FR',
                'period' => $period,
                'last_number' => 0,
            ]);
            $sequence = DocumentSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
        }

        $sequence->increment('last_number');
        $sequence->refresh();

        return sprintf('FR/%s/%06d', $period, $sequence->last_number);
    }
}
