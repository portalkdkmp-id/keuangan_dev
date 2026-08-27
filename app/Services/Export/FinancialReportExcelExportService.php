<?php

namespace App\Services\Export;

use App\Enums\AccountabilityStatus;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class FinancialReportExcelExportService
{
    public function __construct(private readonly SubmissionExcelExportService $submissions) {}

    public function generate(User $user, array $filters, string $type): string
    {
        $directory = storage_path('app/private/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $paths = [];
        $baseWorkbook = $this->submissions->generate($user, $filters);

        if ($type === 'complete') {
            $paths['1. Rekap Pengajuan Dana'] = $this->extractSubmissionSheet($baseWorkbook, $directory, $token);
        }
        if (in_array($type, ['accountability', 'complete'], true)) {
            $paths['2. LPJ'] = $this->writeAccountabilitySheet($user, $filters, "{$directory}/{$token}-lpj.xml");
        }
        if (in_array($type, ['aging', 'complete'], true)) {
            $paths['3. Aging-Outstanding'] = $this->writeAgingSheet($user, $filters, "{$directory}/{$token}-aging.xml");
        }

        abort_if($paths === [], 404);
        $output = "{$directory}/laporan-{$type}-".now()->format('Ymd-His')."-{$token}.xlsx";
        $this->createWorkbook($output, $paths, $baseWorkbook);

        foreach ($paths as $path) {
            @unlink($path);
        }
        @unlink($baseWorkbook);

        return $output;
    }

    private function reportQuery(User $user, array $filters): Builder
    {
        return FundAccountabilityReport::query()
            ->when($user->hasRole('pic_kdkmp'), fn (Builder $query) => $query->where('submitted_by', $user->id))
            ->when($filters['cooperative_id'] ?? null, fn (Builder $query, string $id) => $query->whereHas('submission', fn (Builder $submission) => $submission->where('cooperative_id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->whereHas('submission', fn (Builder $submission) => $submission->where('status', $status)))
            ->when(! $user->hasRole('pic_kdkmp') && ($filters['pic_id'] ?? null), fn (Builder $query) => $query->where('submitted_by', $filters['pic_id']))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->whereHas('submission', fn (Builder $submission) => $submission
                ->where('title', 'like', "%{$search}%")
                ->orWhere('submission_number', 'like', "%{$search}%")
                ->orWhereHas('items', fn (Builder $items) => $items->where('description', 'like', "%{$search}%"))));
    }

    private function outstandingQuery(User $user, array $filters): Builder
    {
        return FinancialSubmission::query()
            ->whereNotNull('disbursed_at')
            ->whereDoesntHave('accountabilityReport', fn (Builder $report) => $report->whereIn('status', [AccountabilityStatus::APPROVED, AccountabilityStatus::CLOSED]))
            ->when($user->hasRole('pic_kdkmp'), fn (Builder $query) => $query->where('submitted_by', $user->id))
            ->when($filters['cooperative_id'] ?? null, fn (Builder $query, string $id) => $query->where('cooperative_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(! $user->hasRole('pic_kdkmp') && ($filters['pic_id'] ?? null), fn (Builder $query) => $query->where('submitted_by', $filters['pic_id']))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('submission_number', 'like', "%{$search}%")
                ->orWhereHas('items', fn (Builder $items) => $items->where('description', 'like', "%{$search}%"))));
    }

    private function writeAccountabilitySheet(User $user, array $filters, string $path): string
    {
        $headers = ['No', 'No. Pengajuan (Ref)', 'No. LPJ', 'KDKMP / PIC', 'Jenis Pengajuan', 'Nominal Dicairkan (Rp)', 'Nominal Dipertanggungjawabkan (Rp)', 'Selisih (Rp)', 'Status Pengembalian Selisih', 'Tanggal Pengembalian', 'Tanggal Dana Cair', 'Tanggal LPJ Diajukan', 'Durasi (Cair ke LPJ, hari)', 'Status Verifikasi LPJ (Layer 1-3)', 'Jumlah Bukti / Attachment'];
        $writer = $this->openSheet($path, [36, 20, 20, 27, 20, 21, 25, 18, 25, 18, 18, 20, 19, 25, 18]);
        $this->writeHeader($writer, 'LAPORAN PERTANGGUNGJAWABAN (LPJ)', 'Cakupan sesuai filter laporan | Periode: '.$this->periodLabel($filters).' | PT Agrinas Pangan Nusantara', $headers, 15);
        $row = 6;
        $number = 1;
        $disbursed = $realized = $difference = $returned = 0.0;

        $this->reportQuery($user, $filters)->with(['submission.cooperative:id,name', 'submission.submitter:id,name', 'submission.requestCategory:id,name', 'fundReturn', 'attachments:id,fund_accountability_report_id', 'items.attachments:id,fund_accountability_item_id'])->orderBy('id')->chunkById(500, function ($reports) use ($writer, &$row, &$number, &$disbursed, &$realized, &$difference, &$returned) {
            foreach ($reports as $report) {
                $submission = $report->submission;
                $variance = (float) $report->remaining_amount - (float) $report->additional_amount;
                $returnedAmount = (float) ($report->fundReturn?->returned_amount ?? 0);
                $disbursed += (float) $report->received_amount;
                $realized += (float) $report->realized_amount;
                $difference += $variance;
                $returned += $returnedAmount;
                $this->writeRow($writer, $row++, [
                    $number++, $submission?->submission_number ?? '-', $report->report_number,
                    $submission?->cooperative?->name ?? $submission?->submitter?->name ?? '-',
                    $submission?->requestCategory?->name ?? '-', (float) $report->received_amount,
                    (float) $report->realized_amount, $variance, $this->returnStatus($report),
                    $this->excelDate($report->fundReturn?->transferred_at), $this->excelDate($submission?->disbursed_at),
                    $this->excelDate($report->submitted_at), $this->duration($submission?->disbursed_at, $report->submitted_at),
                    $this->accountabilityStatus($report->status), $report->attachments->count() + $report->items->sum(fn ($item) => $item->attachments->count()),
                ], [5, 7, 7, 7, 7, 8, 8, 8, 7, 6, 6, 6, 5, 7, 5]);
            }
        });

        $start = max($row + 2, 28);
        $this->writeRow($writer, $start, ['RINGKASAN TOTAL'], [16]);
        $this->writeRow($writer, $start + 1, ['Total Dana Dicairkan', $disbursed], [13, 11]);
        $this->writeRow($writer, $start + 2, ['Total Dana Dipertanggungjawabkan', $realized], [13, 11]);
        $this->writeRow($writer, $start + 3, ['Total Selisih (Variance)', $difference], [13, 11]);
        $this->writeRow($writer, $start + 4, ['  - Sudah Dikembalikan', $returned], [13, 11]);
        $this->writeRow($writer, $start + 5, ['  - Outstanding (Belum Dikembalikan)', max(0, $difference - $returned)], [13, 11]);
        $this->writeRow($writer, $start + 6, ['% Tingkat Pertanggungjawaban', $disbursed > 0 ? $realized / $disbursed : 0], [13, 14]);
        $this->closeSheet($writer, 15);

        return $path;
    }

    private function writeAgingSheet(User $user, array $filters, string $path): string
    {
        $headers = ['No', 'No. Pengajuan', 'Jenis Pengajuan', 'KDKMP / Outlet', 'Nominal Dicairkan (Rp)', 'Tanggal Dana Cair', 'Umur Dana (Hari)', 'Status LPJ', 'Bucket Aging', 'PIC Penanggung Jawab'];
        $writer = $this->openSheet($path, [8, 20, 22, 27, 22, 18, 17, 21, 16, 24]);
        $this->writeHeader($writer, 'LAPORAN AGING / OUTSTANDING', 'Dana cair yang belum selesai di-LPJ-kan | Per tanggal: '.now()->format('d/m/Y').' | PT Agrinas Pangan Nusantara', $headers, 10);
        $row = 6;
        $number = 1;
        $count = 0;
        $total = 0.0;
        $buckets = ['0-7 hari' => [0, 0.0], '8-14 hari' => [0, 0.0], '15-30 hari' => [0, 0.0], '>30 hari' => [0, 0.0]];

        $this->outstandingQuery($user, $filters)->with(['cooperative:id,name', 'submitter:id,name', 'requestCategory:id,name', 'accountabilityReport'])->orderBy('id')->chunkById(500, function ($submissions) use ($writer, &$row, &$number, &$count, &$total, &$buckets) {
            foreach ($submissions as $submission) {
                $age = max(0, (int) $submission->disbursed_at->startOfDay()->diffInDays(today()));
                $bucket = match (true) {
                    $age <= 7 => '0-7 hari', $age <= 14 => '8-14 hari', $age <= 30 => '15-30 hari', default => '>30 hari'
                };
                $amount = (float) ($submission->disbursed_amount ?: $submission->director_approved_amount ?: $submission->total_amount);
                $count++;
                $total += $amount;
                $buckets[$bucket][0]++;
                $buckets[$bucket][1] += $amount;
                $this->writeRow($writer, $row++, [$number++, $submission->submission_number, $submission->requestCategory?->name ?? '-', $submission->cooperative?->name ?? 'Internal', $amount, $this->excelDate($submission->disbursed_at), $age, $this->agingStatus($submission->accountabilityReport?->status), $bucket, $submission->submitter?->name ?? '-'], [5, 7, 7, 7, 8, 6, 5, 7, 7, 7]);
            }
        });

        $start = max($row + 2, 28);
        $this->writeRow($writer, $start, ['RINGKASAN TOTAL'], [16]);
        $this->writeRow($writer, $start + 1, ['Bucket Aging', 'Jumlah Pengajuan', 'Total Nominal Outstanding (Rp)'], [13, 13, 13]);
        $offset = 2;
        foreach ($buckets as $bucket => [$bucketCount, $bucketTotal]) {
            $this->writeRow($writer, $start + $offset++, [$bucket, $bucketCount, $bucketTotal], [13, 10, 11]);
        }
        $this->writeRow($writer, $start + $offset, ['TOTAL OUTSTANDING', $count, $total], [16, 16, 12]);
        $this->closeSheet($writer, 10);

        return $path;
    }

    private function openSheet(string $path, array $widths): XMLWriter
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
        foreach ($widths as $index => $width) {
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

    private function writeHeader(XMLWriter $writer, string $title, string $subtitle, array $headers, int $columns): void
    {
        $this->writeRow($writer, 1, [$title], [1]);
        $this->writeRow($writer, 2, [$subtitle], [2]);
        $this->writeRow($writer, 3, ['Legenda: data dan ringkasan dihitung otomatis berdasarkan filter export'], [3]);
        $this->writeRow($writer, 5, $headers, array_fill(0, $columns, 4));
    }

    private function writeRow(XMLWriter $writer, int $rowNumber, array $values, array $styles = []): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        if (in_array(4, $styles, true)) {
            $writer->writeAttribute('ht', '42');
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
                $writer->writeElement('t', (string) ($value ?? ''));
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
    }

    private function closeSheet(XMLWriter $writer, int $columns): void
    {
        $writer->endElement();
        $writer->startElement('mergeCells');
        $writer->writeAttribute('count', '3');
        foreach ([1, 2, 3] as $row) {
            $writer->startElement('mergeCell');
            $writer->writeAttribute('ref', "A{$row}:".$this->columnName($columns).$row);
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function createWorkbook(string $output, array $sheets, ?string $baseWorkbook): void
    {
        $zip = new ZipArchive;
        $zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $styles = $this->zipEntry($baseWorkbook, 'xl/styles.xml');
        $overrides = $relationships = $sheetEntries = '';
        foreach (array_keys($sheets) as $index => $name) {
            $id = $index + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$id.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $relationships .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
            $sheetEntries .= '<sheet name="'.htmlspecialchars($name, ENT_XML1).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
            $zip->addFile(array_values($sheets)[$index], 'xl/worksheets/sheet'.$id.'.xml');
        }
        $styleId = count($sheets) + 1;
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$overrides.'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheetEntries.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->close();
    }

    private function extractSubmissionSheet(string $workbook, string $directory, string $token): string
    {
        $path = "{$directory}/{$token}-submissions.xml";
        file_put_contents($path, $this->zipEntry($workbook, 'xl/worksheets/sheet1.xml'));

        return $path;
    }

    private function zipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $contents = $zip->getFromName($entry);
        $zip->close();
        if ($contents === false) {
            throw new RuntimeException("Entry {$entry} tidak ditemukan.");
        }

        return $contents;
    }

    private function returnStatus(FundAccountabilityReport $report): string
    {
        if ((float) $report->remaining_amount <= 0) {
            return 'N.A.';
        }

        return $report->fundReturn?->approved_at || $report->fundReturn?->closed_at ? 'Sudah Dikembalikan' : 'Belum Dikembalikan';
    }

    private function accountabilityStatus(AccountabilityStatus $status): string
    {
        return match ($status) {
            AccountabilityStatus::DRAFT => 'Draft', AccountabilityStatus::SUBMITTED, AccountabilityStatus::FINANCE_REVIEW => 'Verifikasi Finance', AccountabilityStatus::REVISION_REQUESTED => 'Revisi', AccountabilityStatus::FINANCE_VERIFIED => 'Menunggu Approval', AccountabilityStatus::APPROVED, AccountabilityStatus::CLOSED => 'Disetujui', default => 'Penyelesaian Selisih'
        };
    }

    private function agingStatus(?AccountabilityStatus $status): string
    {
        return match ($status) {
            null => 'Belum Diajukan', AccountabilityStatus::DRAFT => 'Draft', AccountabilityStatus::REVISION_REQUESTED => 'Revisi', default => 'Dalam Verifikasi'
        };
    }

    private function duration(?DateTimeInterface $from, ?DateTimeInterface $to): int|string
    {
        return $from && $to ? max(0, (int) $from->diff($to)->format('%a')) : '';
    }

    private function excelDate(?DateTimeInterface $date): float|string
    {
        return $date ? 25569 + ($date->getTimestamp() / 86400) : '';
    }

    private function periodLabel(array $filters): string
    {
        $from = isset($filters['created_from']) ? date('d/m/Y', strtotime($filters['created_from'])) : null;
        $to = isset($filters['created_to']) ? date('d/m/Y', strtotime($filters['created_to'])) : null;

        return $from && $to ? "{$from} - {$to}" : ($from ? "Mulai {$from}" : ($to ? "Sampai {$to}" : 'Semua tanggal'));
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }
}
