<?php

namespace App\Services\Cooperative;

use Illuminate\Support\Str;
use RuntimeException;
use XMLReader;
use ZipArchive;

class CooperativeExcelReader
{
    public function rows(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibuka.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetPath = $this->firstSheetPath($zip);
        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Worksheet pertama tidak ditemukan.');
        }

        return $this->parseRows($xml, $sharedStrings);
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $reader = new XMLReader;
        $reader->XML($xml);
        $strings = [];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $node = simplexml_load_string($reader->readOuterXml());
                $node->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $texts = array_map(fn ($text) => (string) $text, $node->xpath('.//x:t') ?: []);
                $strings[] = implode('', $texts);
            }
        }

        return $strings;
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheet = ($workbook->xpath('//x:sheet') ?: [])[0] ?? null;

        if (! $sheet) {
            throw new RuntimeException('Workbook tidak memiliki sheet.');
        }

        $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationId = (string) $attributes['id'];
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));

        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationId) {
                return 'xl/'.ltrim((string) $relationship['Target'], '/');
            }
        }

        throw new RuntimeException('Relasi worksheet tidak ditemukan.');
    }

    private function parseRows(string $xml, array $sharedStrings): array
    {
        $reader = new XMLReader;
        $reader->XML($xml);
        $rows = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowNode = simplexml_load_string($reader->readOuterXml());
            $rowNode->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $row = [];

            foreach ($rowNode->xpath('.//x:c') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                $columnIndex = $this->columnIndex(preg_replace('/\d+/', '', $reference));
                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                $row[$columnIndex] = $type === 's' ? ($sharedStrings[(int) $value] ?? '') : $value;
            }

            if ($row !== []) {
                ksort($row);
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($value) => $this->headerKey($value), array_shift($rows));

        return collect($rows)
            ->map(function (array $row) use ($headers): array {
                $mapped = [];
                foreach ($headers as $index => $header) {
                    if ($header !== '') {
                        $mapped[$header] = trim((string) ($row[$index] ?? ''));
                    }
                }

                return $mapped;
            })
            ->filter(fn (array $row) => collect($row)->filter()->isNotEmpty())
            ->values()
            ->all();
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord(strtoupper($letter)) - 64);
        }

        return $index - 1;
    }

    private function headerKey(string $value): string
    {
        return Str::of($value)->lower()->replace(['/', '-'], ' ')->squish()->replace(' ', '_')->toString();
    }
}
