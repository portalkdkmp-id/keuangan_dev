import { FileText, ZoomIn } from 'lucide-react';
import { useState } from 'react';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';

export function ReportDetail({ report }: any) {
    const [preview, setPreview] = useState<any>(null);
    return (
        <>
            <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
                <div>Dana diterima: {rupiah(report.received_amount)}</div>
                <div>Realisasi: {rupiah(report.realized_amount)}</div>
                <div>Sisa dana: {rupiah(report.remaining_amount)}</div>
                <div>Kekurangan: {rupiah(report.additional_amount)}</div>
                <div>
                    Periode: {formatDate(report.usage_date_from)} -{' '}
                    {formatDate(report.usage_date_to)}
                </div>
                <div>Status: {report.status}</div>
                <div className="md:col-span-2">Ringkasan: {report.summary}</div>
                <div className="md:col-span-2">
                    Catatan Finance: {report.finance_notes ?? '-'}
                </div>
                <div className="md:col-span-2">
                    Catatan Approval: {report.approval_notes ?? '-'}
                </div>
            </div>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Deskripsi</TableHead>
                            <TableHead>Kategori</TableHead>
                            <TableHead>Vendor</TableHead>
                            <TableHead>Nominal</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {report.items.map((item: any) => (
                            <TableRow key={item.id}>
                                <TableCell>
                                    {formatDate(item.expense_date)}
                                </TableCell>
                                <TableCell>{item.description}</TableCell>
                                <TableCell>
                                    {item.category_name_snapshot ?? '-'}
                                </TableCell>
                                <TableCell>{item.vendor_name ?? '-'}</TableCell>
                                <TableCell>{rupiah(item.amount)}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            <div className="space-y-3 rounded-md border p-4 text-sm">
                <h2 className="font-semibold">Bukti Penggunaan Dana</h2>
                {report.attachments.length ? (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {report.attachments.map((attachment: any) =>
                            attachment.mime_type?.startsWith('image/') ? (
                                <button
                                    key={attachment.id}
                                    type="button"
                                    className="group relative overflow-hidden rounded-md border"
                                    onClick={() => setPreview(attachment)}
                                >
                                    <img
                                        src={`/accountability-attachments/${attachment.id}/preview`}
                                        alt={attachment.original_name}
                                        className="h-44 w-full object-contain"
                                    />
                                    <span className="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100">
                                        <ZoomIn className="size-5" />
                                    </span>
                                    <span className="block truncate border-t p-2 text-left text-xs">
                                        {attachment.original_name}
                                    </span>
                                </button>
                            ) : (
                                <a
                                    key={attachment.id}
                                    href={`/accountability-attachments/${attachment.id}/download`}
                                    className="flex h-44 flex-col items-center justify-center gap-2 rounded-md border bg-muted p-3 text-center"
                                >
                                    <FileText className="size-8" />
                                    <span className="text-xs break-all">
                                        {attachment.original_name}
                                    </span>
                                </a>
                            ),
                        )}
                    </div>
                ) : (
                    <span className="text-muted-foreground">
                        Belum ada bukti.
                    </span>
                )}
            </div>
            <Dialog
                open={Boolean(preview)}
                onOpenChange={(open) => !open && setPreview(null)}
            >
                <DialogContent className="max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>{preview?.original_name}</DialogTitle>
                    </DialogHeader>
                    {preview && (
                        <img
                            src={`/accountability-attachments/${preview.id}/preview`}
                            alt={preview.original_name}
                            className="max-h-[80vh] w-full object-contain"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
