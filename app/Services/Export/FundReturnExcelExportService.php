<?php

namespace App\Services\Export;

use App\Enums\FundReturnStatus;
use App\Models\FundReturn;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class FundReturnExcelExportService
{
    private const HEADERS = [
        'No', 'Nomor Pengembalian', 'Nomor Pengajuan', 'Judul Pengajuan', 'Pengirim',
        'Status', 'Nominal Wajib Dikembalikan', 'Nominal Dikembalikan', 'Metode',
        'Waktu Transfer', 'Rekening Sumber', 'Rekening Tujuan', 'Tanggal Diajukan',
        'Tanggal Verifikasi Finance', 'Catatan', 'Bukti Pengembalian',
    ];

    public function generate(): string
    {
        $directory = storage_path('app/private/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $sheetPath = "{$directory}/{$token}-fund-returns.xml";
        $output = "{$directory}/pengembalian-dana-{$token}.xlsx";
        $writer = $this->openSheet($sheetPath);

        $this->writeRow($writer, 1, ['LAPORAN PENGEMBALIAN DANA']);
        $this->writeRow($writer, 2, ['Diekspor pada '.now()->format('d/m/Y H:i')]);
        $this->writeRow($writer, 4, self::HEADERS);

        $row = 5;
        $number = 1;
        FundReturn::query()
            ->with(['submission:id,submission_number,title', 'returner:id,name', 'attachments:id,fund_return_id,original_name'])
            ->latest('created_at')
            ->chunk(500, function ($returns) use ($writer, &$row, &$number) {
                foreach ($returns as $return) {
                    $this->writeRow($writer, $row++, $this->row($return, $number++));
                }
            });

        $this->closeSheet($writer);
        $this->createWorkbook($output, $sheetPath);
        @unlink($sheetPath);

        return $output;
    }

    private function row(FundReturn $return, int $number): array
    {
        return [
            $number,
            $return->return_number,
            $return->submission?->submission_number ?? '-',
            $return->submission?->title ?? '-',
            $return->returner?->name ?? '-',
            $this->statusLabel($return->status),
            (float) $return->expected_amount,
            (float) $return->returned_amount,
            str($return->payment_method)->replace('_', ' ')->title()->toString(),
            $return->transferred_at?->format('d/m/Y H:i') ?? $return->transfer_date?->format('d/m/Y'),
            $this->account($return->source_bank_name_snapshot, $return->source_account_number_snapshot, $return->source_account_holder_snapshot),
            $this->account($return->destination_bank_name_snapshot, $return->destination_account_number_snapshot, $return->destination_account_holder_snapshot),
            $return->submitted_at?->format('d/m/Y H:i') ?? '-',
            $return->verified_at?->format('d/m/Y H:i') ?? '-',
            $return->verification_notes ?: $return->notes ?: '-',
            $return->attachments->pluck('original_name')->implode(', ') ?: '-',
        ];
    }

    private function account(?string $bank, ?string $number, ?string $holder): string
    {
        return collect([$bank, $number, $holder])->filter()->implode(' - ') ?: '-';
    }

    private function statusLabel(FundReturnStatus $status): string
    {
        return match ($status) {
            FundReturnStatus::DRAFT => 'Draft',
            FundReturnStatus::SUBMITTED => 'Menunggu Finance',
            FundReturnStatus::FINANCE_REVIEW => 'Review Finance',
            FundReturnStatus::REVISION_REQUESTED => 'Perlu Revisi',
            FundReturnStatus::FINANCE_VERIFIED => 'Menunggu Approval',
            FundReturnStatus::REJECTED => 'Ditolak',
            FundReturnStatus::CLOSED => 'Selesai',
        };
    }

    private function openSheet(string $path): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openUri($path);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $writer->startElement('sheetData');

        return $writer;
    }

    private function writeRow(XMLWriter $writer, int $rowNumber, array $values): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        foreach (array_values($values) as $index => $value) {
            $writer->startElement('c');
            $writer->writeAttribute('r', $this->columnName($index + 1).$rowNumber);
            $writer->writeAttribute('t', is_int($value) || is_float($value) ? 'n' : 'inlineStr');
            if (is_int($value) || is_float($value)) {
                $writer->writeElement('v', (string) $value);
            } else {
                $writer->startElement('is');
                $writer->writeElement('t', (string) $value);
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
    }

    private function closeSheet(XMLWriter $writer): void
    {
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function createWorkbook(string $output, string $sheetPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Workbook export tidak dapat dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Pengembalian Dana" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
