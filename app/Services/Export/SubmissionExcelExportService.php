<?php

namespace App\Services\Export;

use App\Models\FinancialSubmission;
use App\Models\User;
use App\Repositories\SubmissionExportRepository;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class SubmissionExcelExportService
{
    public function __construct(private readonly SubmissionExportRepository $submissions) {}

    public function generate(User $user, array $filters = [], ?FinancialSubmission $single = null): string
    {
        $directory = storage_path('app/private/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $submissionSheet = "{$directory}/{$token}-submissions.xml";
        $itemSheet = "{$directory}/{$token}-items.xml";
        $attachmentSheet = "{$directory}/{$token}-attachments.xml";
        $historySheet = "{$directory}/{$token}-histories.xml";
        $output = "{$directory}/pengajuan-".now()->format('Ymd-His')."-{$token}.xlsx";

        $submissionWriter = $this->openSheet($submissionSheet, ['Nomor Pengajuan', 'Tipe', 'Status Akhir', 'Judul', 'Pengaju', 'Email Pengaju', 'Area', 'Koperasi', 'Kategori', 'Jenis', 'Nominal Pengajuan', 'Tanggal Dibutuhkan', 'Tanggal Dibuat', 'Terakhir Update', 'Terakhir Update Status', 'Finance Validator', 'Nominal Approval', 'Finance Approver', 'Nominal Director', 'Finance Director', 'Tanggal Pencairan', 'Nominal Dicairkan', 'Catatan', 'Jumlah Item', 'Jumlah Attachment']);
        $itemWriter = $this->openSheet($itemSheet, ['Nomor Pengajuan', 'Judul Pengajuan', 'Nama Item', 'Jenis Item', 'Jenis Lainnya', 'Nominal', 'Urutan']);
        $attachmentWriter = $this->openSheet($attachmentSheet, ['Nomor Pengajuan', 'Judul', 'Nama File', 'Jenis Attachment', 'MIME Type', 'Ukuran (byte)', 'URL Download']);
        $historyWriter = $this->openSheet($historySheet, ['Nomor Pengajuan', 'Status Awal', 'Status Tujuan', 'Aksi', 'Diubah Oleh', 'Catatan', 'Waktu']);
        $submissionRow = 2;
        $itemRow = 2;
        $attachmentRow = 2;
        $historyRow = 2;

        $this->submissions->eachChunk($user, $filters, function ($submissions) use ($submissionWriter, $itemWriter, $attachmentWriter, $historyWriter, &$submissionRow, &$itemRow, &$attachmentRow, &$historyRow) {
            foreach ($submissions as $submission) {
                $this->writeRow($submissionWriter, $submissionRow++, $this->submissionRow($submission));
                foreach ($submission->items as $item) {
                    $this->writeRow($itemWriter, $itemRow++, [
                        $submission->submission_number, $submission->title, $item->description,
                        $item->requestType?->name ?? $item->request_type_name, $item->other_type_name, (float) $item->subtotal, $item->sort_order,
                    ]);
                }
                foreach ($submission->attachments as $attachment) {
                    $this->writeRow($attachmentWriter, $attachmentRow++, [
                        $submission->submission_number,
                        $submission->title,
                        $attachment->original_name,
                        $attachment->attachment_type?->value ?? (string) $attachment->attachment_type,
                        $attachment->mime_type,
                        $attachment->size,
                        route('submission-attachments.download', $attachment),
                    ]);
                }
                foreach ($submission->statusHistories as $history) {
                    $this->writeRow($historyWriter, $historyRow++, [
                        $submission->submission_number,
                        $history->from_status?->value,
                        $history->to_status->value,
                        $history->action,
                        $history->actor?->name,
                        $history->notes,
                        $history->created_at?->format('d/m/Y H:i:s'),
                    ]);
                }
            }
        }, $single);

        $this->closeSheet($submissionWriter);
        $this->closeSheet($itemWriter);
        $this->closeSheet($attachmentWriter);
        $this->closeSheet($historyWriter);
        $sheets = ['Pengajuan' => $submissionSheet, 'Items' => $itemSheet, 'Attachments' => $attachmentSheet, 'Riwayat Status' => $historySheet];
        $this->createWorkbook($output, $sheets);
        foreach ($sheets as $sheet) {
            @unlink($sheet);
        }

        return $output;
    }

    private function submissionRow(FinancialSubmission $submission): array
    {
        return [
            $submission->submission_number,
            $submission->type->value,
            $submission->status->value,
            $submission->title,
            $submission->submitter?->name,
            $submission->submitter?->email,
            $submission->submitterCity?->name,
            $submission->cooperative?->name,
            $submission->requestCategory?->name,
            $submission->requestType?->name,
            (float) $submission->total_amount,
            $submission->needed_date?->format('d/m/Y'),
            $submission->created_at?->format('d/m/Y H:i:s'),
            $submission->updated_at?->format('d/m/Y H:i:s'),
            $submission->status_histories_max_created_at ? date('d/m/Y H:i:s', strtotime($submission->status_histories_max_created_at)) : null,
            $submission->financeValidator?->name,
            $submission->approval_approved_amount ? (float) $submission->approval_approved_amount : null,
            $submission->approvalDecisionMaker?->name,
            $submission->director_approved_amount ? (float) $submission->director_approved_amount : null,
            $submission->directorDecisionMaker?->name,
            $submission->disbursed_at?->format('d/m/Y H:i:s'),
            $submission->disbursed_amount ? (float) $submission->disbursed_amount : null,
            $submission->notes,
            $submission->items->count(),
            $submission->attachments->count(),
        ];
    }

    private function openSheet(string $path, array $headers): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openUri($path);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $writer->startElement('sheetData');
        $this->writeRow($writer, 1, $headers, true);

        return $writer;
    }

    private function closeSheet(XMLWriter $writer): void
    {
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function writeRow(XMLWriter $writer, int $rowNumber, array $values, bool $header = false): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        foreach (array_values($values) as $index => $value) {
            $reference = $this->columnName($index + 1).$rowNumber;
            $writer->startElement('c');
            $writer->writeAttribute('r', $reference);
            $writer->writeAttribute('t', is_int($value) || is_float($value) ? 'n' : 'inlineStr');
            if ($header) {
                $writer->writeAttribute('s', '1');
            }
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

    private function createWorkbook(string $output, array $sheets): void
    {
        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Workbook export tidak dapat dibuat.');
        }
        $overrides = collect($sheets)->keys()->map(fn ($name, $index) => '<Override PartName="/xl/worksheets/sheet'.($index + 1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>')->implode('');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.$overrides.'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $sheetNodes = collect($sheets)->keys()->map(fn ($name, $index) => '<sheet name="'.htmlspecialchars($name, ENT_XML1).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>')->implode('');
        $relationships = collect($sheets)->keys()->map(fn ($name, $index) => '<Relationship Id="rId'.($index + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($index + 1).'.xml"/>')->implode('');
        $styleId = count($sheets) + 1;
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheetNodes.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font/><font><b/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf xfId="0"/><xf xfId="0" fontId="1" applyFont="1"/></cellXfs></styleSheet>');
        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFile($sheet, 'xl/worksheets/sheet'.($index + 1).'.xml');
        }
        $zip->close();
    }
}
