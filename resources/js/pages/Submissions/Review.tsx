import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FileText } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';
import { SubmissionItemsTable } from '@/components/Submissions/SubmissionItemsTable';

export default function SubmissionsReview({ submission }: any) {
    const submitForm = useForm({});
    const [confirmOpen, setConfirmOpen] = useState(false);

    const submit = () => {
        sessionStorage.setItem(
            'submission-submitted',
            JSON.stringify({
                id: submission.id,
                number: submission.submission_number,
                title: submission.title,
            }),
        );
        submitForm.post(`/submissions/${submission.id}/submit`, {
            preserveScroll: true,
            onSuccess: () => {
                setConfirmOpen(false);
            },
            onError: () => sessionStorage.removeItem('submission-submitted'),
        });
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="Review Pengajuan" />
            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Review Pengajuan</h1>
                    <p className="text-sm text-muted-foreground">
                        {submission.submission_number} - {submission.title}
                    </p>
                </div>
                <SubmissionStatusBadge status={submission.status} />
            </div>
            <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
                <div>Nomor Pengajuan: {submission.submission_number}</div>
                <div>Title: {submission.title}</div>
                <div>Koperasi: {submission.cooperative?.name}</div>
                <div>Kategori: {submission.request_category?.name}</div>
                <div>Jenis: {submission.request_type?.name}</div>
                <div>Nominal: {rupiah(submission.total_amount)}</div>
                <div>
                    Rekening Tujuan:{' '}
                    {submission.recipient_bank_account
                        ? `${submission.recipient_bank_account.bank_name} - ${submission.recipient_bank_account.account_holder_name} - ${submission.recipient_bank_account.account_number}`
                        : '-'}
                </div>
                <div>
                    Tanggal Dibutuhkan: {formatDate(submission.needed_date)}
                </div>
                <div className="md:col-span-2">
                    Catatan: {submission.notes ?? '-'}
                </div>
            </div>
            <div className="space-y-3">
                <h2 className="font-semibold">Item Pengajuan</h2>
                <SubmissionItemsTable items={submission.items} />
            </div>
            <div className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Attachment</h2>
                {submission.attachments?.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Belum ada lampiran pengajuan dana.
                    </p>
                )}
                <div className="grid gap-3 md:grid-cols-2">
                    {submission.attachments?.map((attachment: any) => (
                        <div
                            key={attachment.id}
                            className="rounded-md border p-3 text-sm"
                        >
                            {attachment.mime_type?.startsWith('image/') ? (
                                <img
                                    src={`/submission-attachments/${attachment.id}/preview`}
                                    alt={attachment.original_name}
                                    className="mb-2 max-h-80 w-full rounded-md object-contain"
                                />
                            ) : (
                                <div className="mb-2 flex h-32 items-center justify-center rounded-md bg-muted">
                                    <FileText className="size-10" />
                                </div>
                            )}
                            <Link
                                className="underline"
                                href={`/submission-attachments/${attachment.id}/download`}
                            >
                                {attachment.original_name}
                            </Link>
                        </div>
                    ))}
                </div>
            </div>
            <div className="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild>
                    <Link href={`/submissions/${submission.id}/edit`}>
                        Kembali
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    onClick={() => {
                        toast.success('Draft berhasil disimpan.');
                        router.get('/submissions', {}, { replace: true });
                    }}
                >
                    Simpan Draft
                </Button>
                <Button onClick={() => setConfirmOpen(true)}>
                    Ajukan ke Keuangan
                </Button>
            </div>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan Pengajuan?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pastikan seluruh data pengajuan sudah benar.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Setelah diajukan, pengajuan akan diteruskan ke Staff
                        Keuangan untuk dilakukan review.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button
                            disabled={submitForm.processing}
                            onClick={submit}
                        >
                            Ajukan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
