<?php

namespace App\Services\Export;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\User;
use App\Repositories\SubmissionExportRepository;
use DateTimeInterface;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class SubmissionExcelExportService
{
    private const HEADERS = ['No', 'No. Pengajuan', 'Tanggal Pengajuan', 'Jenis Pengajuan', 'KDKMP / Outlet', 'PIC Pengaju', 'Sub-Kategori', 'Ringkasan Item', 'Nama Item', 'Nominal per Item', 'Total Nominal Diajukan (Rp)', 'Tingkat Urgensi', 'Status Pengajuan', 'Layer Approval / Saat Ini', 'Jumlah Attachment'];

    public function __construct(private readonly SubmissionExportRepository $submissions) {}

    public function generate(User $user, array $filters = [], ?FinancialSubmission $single = null): string
    {
        $directory = storage_path('app/private/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $sheetPath = "{$directory}/{$token}-rekap.xml";
        $output = "{$directory}/pengajuan-".now()->format('Ymd-His')."-{$token}.xlsx";
        $writer = $this->openSheet($sheetPath);
        $this->writeReportHeader($writer, $filters, $single);

        $row = 6;
        $number = 1;
        $summary = [];
        $approvedCount = 0;
        $totalCount = 0;
        $totalSubmitted = 0.0;

        $this->submissions->eachChunk($user, $filters, function ($submissions) use ($writer, &$row, &$number, &$summary, &$approvedCount, &$totalCount, &$totalSubmitted) {
            foreach ($submissions as $submission) {
                $this->writeRow($writer, $row++, $this->submissionRow($submission, $number++), [5, 5, 6, 5, 5, 7, 5, 7, 7, 7, 8, 5, 5, 5, 5]);
                $this->accumulateSummary($summary, $submission);
                $totalCount++;
                $totalSubmitted += (float) $submission->total_amount;
                if ($this->isApproved($submission->status)) {
                    $approvedCount++;
                }
            }
        }, $single);

        $summaryStart = max($row + 2, 28);
        $this->writeSummary($writer, $summaryStart, $summary, $totalCount, $totalSubmitted, $approvedCount);
        $this->closeSheet($writer);
        $this->createWorkbook($output, $sheetPath);
        @unlink($sheetPath);

        return $output;
    }

    private function writeReportHeader(XMLWriter $writer, array $filters, ?FinancialSubmission $single): void
    {
        $period = $single
            ? $single->created_at?->format('d/m/Y')
            : $this->periodLabel($filters);

        $this->writeRow($writer, 1, ['LAPORAN REKAP PENGAJUAN DANA PERIODIK'], [1]);
        $this->writeRow($writer, 2, ["Program: Permintaan Dana Operasional KDKMP  |  Periode: {$period}  |  PT Agrinas Pangan Nusantara"], [2]);
        $this->writeRow($writer, 3, ['Legenda: data pengajuan dan ringkasan dihitung otomatis berdasarkan filter export'], [3]);
        $this->writeRow($writer, 5, self::HEADERS, array_fill(0, count(self::HEADERS), 4));
    }

    private function submissionRow(FinancialSubmission $submission, int $number): array
    {
        $subCategories = $submission->items
            ->map(fn ($item) => $item->other_type_name ?: ($item->requestType?->name ?? $item->request_type_name))
            ->filter()->unique()->implode(', ');
        $itemNames = $submission->items->pluck('description')->filter()->implode(', ');
        $itemAmounts = $submission->items
            ->map(fn ($item) => ($item->description ?: 'Item').' - Rp'.number_format((float) $item->subtotal, 0, ',', '.'))
            ->implode(', ');

        return [
            $number,
            $submission->submission_number,
            $this->excelDate($submission->created_at),
            $submission->requestCategory?->name ?? $this->typeLabel($submission),
            $submission->cooperative?->name ?? '-',
            $submission->submitter?->name ?? '-',
            $subCategories ?: ($submission->requestType?->name ?? '-'),
            $submission->items->count().' item',
            $itemNames ?: '-',
            $itemAmounts ?: '-',
            (float) $submission->total_amount,
            '-',
            $this->statusLabel($submission->status),
            $this->approvalLayer($submission->status),
            $submission->attachments->count(),
        ];
    }

    private function accumulateSummary(array &$summary, FinancialSubmission $submission): void
    {
        $name = $submission->requestCategory?->name ?? $this->typeLabel($submission);
        $summary[$name] ??= ['count' => 0, 'submitted' => 0.0, 'approved' => 0.0, 'disbursed' => 0.0, 'rejected' => 0.0, 'pending' => 0.0];
        $amount = (float) $submission->total_amount;
        $summary[$name]['count']++;
        $summary[$name]['submitted'] += $amount;
        $summary[$name]['approved'] += $this->isApproved($submission->status) ? (float) ($submission->director_approved_amount ?: $submission->approval_approved_amount ?: $amount) : 0;
        $summary[$name]['disbursed'] += (float) ($submission->disbursed_amount ?? 0);
        $summary[$name]['rejected'] += $this->isRejected($submission->status) ? $amount : 0;
        $summary[$name]['pending'] += $this->isPending($submission->status) ? $amount : 0;
    }

    private function writeSummary(XMLWriter $writer, int $start, array $summary, int $count, float $submitted, int $approvedCount): void
    {
        $this->writeRow($writer, $start, ['RINGKASAN TOTAL'], [9]);
        $this->writeRow($writer, ++$start, ['Jenis Pengajuan', 'Jumlah Pengajuan', 'Total Diajukan (Rp)', 'Total Disetujui* (Rp)', 'Total Dicairkan (Rp)', 'Total Ditolak (Rp)', 'Total Pending** (Rp)'], array_fill(0, 7, 4));
        $totals = ['count' => 0, 'submitted' => 0.0, 'approved' => 0.0, 'disbursed' => 0.0, 'rejected' => 0.0, 'pending' => 0.0];

        foreach ($summary as $name => $values) {
            $this->writeRow($writer, ++$start, [$name, $values['count'], $values['submitted'], $values['approved'], $values['disbursed'], $values['rejected'], $values['pending']], [10, 10, 11, 11, 11, 11, 11]);
            foreach ($totals as $key => $value) {
                $totals[$key] += $values[$key];
            }
        }

        $this->writeRow($writer, ++$start, ['TOTAL', $totals['count'], $totals['submitted'], $totals['approved'], $totals['disbursed'], $totals['rejected'], $totals['pending']], [16, 16, 12, 12, 12, 12, 12]);
        $this->writeRow($writer, $start + 2, ['% Approval Rate', $count > 0 ? $approvedCount / $count : 0], [13, 14]);
        $this->writeRow($writer, $start + 3, ['Rata-rata Nominal per Pengajuan', $count > 0 ? $submitted / $count : 0], [13, 11]);
        $this->writeRow($writer, $start + 5, ['*Disetujui = sudah melewati keputusan approval  **Pending = masih dalam proses submit/verifikasi/approval'], [15]);
    }

    private function openSheet(string $path): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openUri($path);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $writer->startElement('sheetViews');
        $writer->startElement('sheetView');
        $writer->writeAttribute('workbookViewId', '0');
        $writer->startElement('pane');
        $writer->writeAttribute('ySplit', '5');
        $writer->writeAttribute('topLeftCell', 'A6');
        $writer->writeAttribute('state', 'frozen');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->startElement('cols');
        foreach ([16, 20, 16, 23, 25, 21, 23, 17, 28, 34, 22, 18, 19, 18, 16] as $index => $width) {
            $writer->startElement('col');
            $writer->writeAttribute('min', (string) ($index + 1));
            $writer->writeAttribute('max', (string) ($index + 1));
            $writer->writeAttribute('width', (string) $width);
            $writer->writeAttribute('customWidth', '1');
            $writer->endElement();
        }
        $writer->endElement();
        $writer->startElement('sheetData');

        return $writer;
    }

    private function closeSheet(XMLWriter $writer): void
    {
        $writer->endElement();
        $writer->startElement('mergeCells');
        $writer->writeAttribute('count', '3');
        foreach (['A1:O1', 'A2:O2', 'A3:O3'] as $range) {
            $writer->startElement('mergeCell');
            $writer->writeAttribute('ref', $range);
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function writeRow(XMLWriter $writer, int $rowNumber, array $values, array $styles = []): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        if ($rowNumber === 1 || $rowNumber === 5 || $rowNumber >= 6 && (in_array(5, $styles, true) || in_array(13, $styles, true))) {
            $height = match (true) {
                $rowNumber === 1 => '24',
                $rowNumber === 5 => '36',
                in_array(13, $styles, true) => '30',
                default => '28',
            };
            $writer->writeAttribute('ht', $height);
            $writer->writeAttribute('customHeight', '1');
        }
        foreach (array_values($values) as $index => $value) {
            $writer->startElement('c');
            $writer->writeAttribute('r', $this->columnName($index + 1).$rowNumber);
            $writer->writeAttribute('s', (string) ($styles[$index] ?? 0));
            $writer->writeAttribute('t', is_int($value) || is_float($value) ? 'n' : 'inlineStr');
            if (is_int($value) || is_float($value)) {
                $writer->writeElement('v', (string) $value);
            } else {
                $writer->startElement('is');
                $writer->writeElement('t', $value === null ? '' : (string) $value);
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
    }

    private function createWorkbook(string $output, string $sheetPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Workbook export tidak dapat dibuat.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="1. Rekap Pengajuan Dana" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="3"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/><numFmt numFmtId="165" formatCode="&quot;Rp&quot;#,##0"/><numFmt numFmtId="166" formatCode="0.0%"/></numFmts><fonts count="7"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="18"/><color rgb="FF1F4E78"/><name val="Arial"/></font><font><i/><sz val="11"/><color rgb="FF555555"/><name val="Arial"/></font><font><i/><b/><sz val="10"/><color rgb="FFC00000"/><name val="Arial"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font><font><sz val="11"/><color rgb="FF0000FF"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FFB7B7B7"/></left><right style="thin"><color rgb="FFB7B7B7"/></right><top style="thin"><color rgb="FFB7B7B7"/></top><bottom style="thin"><color rgb="FFB7B7B7"/></bottom></border></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="17"><xf xfId="0"/><xf xfId="0" fontId="1"/><xf xfId="0" fontId="2"/><xf xfId="0" fontId="3"/><xf xfId="0" fontId="4" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf xfId="0" fontId="5" fillId="3" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf xfId="0" fontId="5" fillId="3" borderId="1" numFmtId="164" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf xfId="0" fontId="5" fillId="3" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf xfId="0" fontId="5" fillId="3" borderId="1" numFmtId="165" applyNumberFormat="1"/><xf xfId="0" fontId="6"/><xf xfId="0" borderId="1" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf xfId="0" borderId="1" numFmtId="165" applyNumberFormat="1"/><xf xfId="0" fontId="6" fillId="2" borderId="1" numFmtId="165" applyNumberFormat="1"/><xf xfId="0" fontId="6" applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf><xf xfId="0" numFmtId="166" applyNumberFormat="1"/><xf xfId="0" fontId="2"/><xf xfId="0" fontId="6" fillId="2" borderId="1"/></cellXfs></styleSheet>';
    }

    private function periodLabel(array $filters): string
    {
        $from = isset($filters['created_from']) ? date('d/m/Y', strtotime($filters['created_from'])) : null;
        $to = isset($filters['created_to']) ? date('d/m/Y', strtotime($filters['created_to'])) : null;

        return match (true) {
            $from && $to => "{$from} - {$to}",
            (bool) $from => "Mulai {$from}",
            (bool) $to => "Sampai {$to}",
            default => 'Semua tanggal',
        };
    }

    private function excelDate(?DateTimeInterface $date): float|string
    {
        return $date ? 25569 + ($date->getTimestamp() / 86400) : '';
    }

    private function typeLabel(FinancialSubmission $submission): string
    {
        return match ($submission->type->value) {
            'reimbursement' => 'Reimbursement',
            'advance' => 'Panjar',
            default => 'Pengajuan Dana',
        };
    }

    private function statusLabel(SubmissionStatus $status): string
    {
        return match ($status) {
            SubmissionStatus::DRAFT => 'Draft',
            SubmissionStatus::SUBMITTED => 'Submit',
            SubmissionStatus::FINANCE_REVIEW, SubmissionStatus::FINANCE_VALIDATED => 'Verifikasi',
            SubmissionStatus::REVISION_REQUESTED, SubmissionStatus::APPROVAL_REVISION_REQUESTED, SubmissionStatus::DIRECTOR_REVISION_REQUESTED => 'Revisi',
            SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW, SubmissionStatus::DIRECTOR_REVIEW, SubmissionStatus::DIRECTOR_IN_REVIEW => 'Approval',
            SubmissionStatus::PENDING_DISBURSEMENT => 'Menunggu Pencairan',
            SubmissionStatus::FUND_DISBURSED => 'Cair',
            SubmissionStatus::APPROVAL_REJECTED, SubmissionStatus::DIRECTOR_REJECTED, SubmissionStatus::CANCELLED => 'Reject',
        };
    }

    private function approvalLayer(SubmissionStatus $status): int
    {
        return match ($status) {
            SubmissionStatus::DRAFT, SubmissionStatus::SUBMITTED, SubmissionStatus::REVISION_REQUESTED => 1,
            SubmissionStatus::FINANCE_REVIEW, SubmissionStatus::FINANCE_VALIDATED => 2,
            SubmissionStatus::APPROVAL_REVIEW, SubmissionStatus::APPROVAL_IN_REVIEW, SubmissionStatus::APPROVAL_REVISION_REQUESTED, SubmissionStatus::APPROVAL_REJECTED => 3,
            default => 4,
        };
    }

    private function isApproved(SubmissionStatus $status): bool
    {
        return in_array($status, [SubmissionStatus::DIRECTOR_REVIEW, SubmissionStatus::DIRECTOR_IN_REVIEW, SubmissionStatus::DIRECTOR_REVISION_REQUESTED, SubmissionStatus::PENDING_DISBURSEMENT, SubmissionStatus::FUND_DISBURSED], true);
    }

    private function isRejected(SubmissionStatus $status): bool
    {
        return in_array($status, [SubmissionStatus::APPROVAL_REJECTED, SubmissionStatus::DIRECTOR_REJECTED, SubmissionStatus::CANCELLED], true);
    }

    private function isPending(SubmissionStatus $status): bool
    {
        return ! $this->isApproved($status) && ! $this->isRejected($status) && $status !== SubmissionStatus::DRAFT;
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
