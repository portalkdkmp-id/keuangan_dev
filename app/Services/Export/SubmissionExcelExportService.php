<?php

namespace App\Services\Export;

use App\Models\FinancialSubmission;
use App\Repositories\SubmissionExportRepository;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class SubmissionExcelExportService
{
    public function __construct(private readonly SubmissionExportRepository $submissions) {}

    public function generate(): string
    {
        $directory = storage_path('app/private/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $submissionSheet = "{$directory}/{$token}-submissions.xml";
        $attachmentSheet = "{$directory}/{$token}-attachments.xml";
        $output = "{$directory}/pengajuan-".now()->format('Ymd-His')."-{$token}.xlsx";

        $submissionWriter = $this->openSheet($submissionSheet, ['Nomor Pengajuan', 'Tipe', 'Status', 'Judul', 'Pengaju', 'Email Pengaju', 'Area', 'Koperasi', 'Kategori', 'Jenis', 'Nominal Pengajuan', 'Tanggal Dibutuhkan', 'Tanggal Diajukan', 'Finance Validator', 'Nominal Approval', 'Finance Approver', 'Nominal Director', 'Finance Director', 'Tanggal Pencairan', 'Nominal Dicairkan', 'Catatan', 'Jumlah Attachment']);
        $attachmentWriter = $this->openSheet($attachmentSheet, ['Nomor Pengajuan', 'Judul', 'Nama File', 'Jenis Attachment', 'MIME Type', 'Ukuran (byte)', 'URL Download']);
        $submissionRow = 2;
        $attachmentRow = 2;

        $this->submissions->eachChunk(function ($submissions) use ($submissionWriter, $attachmentWriter, &$submissionRow, &$attachmentRow) {
            foreach ($submissions as $submission) {
                $this->writeRow($submissionWriter, $submissionRow++, $this->submissionRow($submission));
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
            }
        });

        $this->closeSheet($submissionWriter);
        $this->closeSheet($attachmentWriter);
        $this->createWorkbook($output, $submissionSheet, $attachmentSheet);
        @unlink($submissionSheet);
        @unlink($attachmentSheet);

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
            $submission->financeValidator?->name,
            $submission->approval_approved_amount ? (float) $submission->approval_approved_amount : null,
            $submission->approvalDecisionMaker?->name,
            $submission->director_approved_amount ? (float) $submission->director_approved_amount : null,
            $submission->directorDecisionMaker?->name,
            $submission->disbursed_at?->format('d/m/Y H:i:s'),
            $submission->disbursed_amount ? (float) $submission->disbursed_amount : null,
            $submission->notes,
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

    private function createWorkbook(string $output, string $submissionSheet, string $attachmentSheet): void
    {
        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Workbook export tidak dapat dibuat.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Pengajuan" sheetId="1" r:id="rId1"/><sheet name="Attachments" sheetId="2" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font/><font><b/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf xfId="0"/><xf xfId="0" fontId="1" applyFont="1"/></cellXfs></styleSheet>');
        $zip->addFile($submissionSheet, 'xl/worksheets/sheet1.xml');
        $zip->addFile($attachmentSheet, 'xl/worksheets/sheet2.xml');
        $zip->close();
    }
}
