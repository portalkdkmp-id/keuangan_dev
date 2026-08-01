<?php

namespace App\Services\DocumentNumber;

use App\Models\DocumentSequence;

class DocumentNumberService
{
    public function generateFundRequestNumber(?\DateTimeInterface $date = null): string
    {
        return $this->generate('FR', 'FR', $date);
    }

    public function generateDisbursementNumber(?\DateTimeInterface $date = null): string
    {
        return $this->generate('DISBURSEMENT', 'DISB', $date);
    }

    public function generateDistributionNumber(?\DateTimeInterface $date = null): string
    {
        return $this->generate('FUND_DISTRIBUTION', 'DIST', $date);
    }

    public function generateAccountabilityNumber(?\DateTimeInterface $date = null): string
    {
        return $this->generate('FUND_ACCOUNTABILITY', 'ACC', $date);
    }

    private function generate(string $documentType, string $prefix, ?\DateTimeInterface $date = null): string
    {
        $date ??= now();
        $period = $date->format('Y/m');

        $sequence = DocumentSequence::query()
            ->where('document_type', $documentType)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            $sequence = DocumentSequence::create([
                'document_type' => $documentType,
                'period' => $period,
                'last_number' => 0,
            ]);
            $sequence = DocumentSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
        }

        $sequence->increment('last_number');
        $sequence->refresh();

        return sprintf('%s/%s/%06d', $prefix, $period, $sequence->last_number);
    }
}
