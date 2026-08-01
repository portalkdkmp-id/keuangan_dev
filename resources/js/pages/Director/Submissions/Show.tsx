import { Head, useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function DirectorSubmissionsShow({ submission }: any) {
    const directorReview = submission.director_reviews?.[0];
    const approvalReview = submission.approval_reviews?.[0];
    const startForm = useForm({});
    const pendingForm = useForm({ approved_amount: submission.approval_approved_amount ?? '', notes: '' });
    const disburseNowForm = useForm({ approved_amount: submission.approval_approved_amount ?? '', transfer_date: '', transferred_at: '', payment_method: 'bank_transfer', bank_name: '', source_account_name: '', source_account_number: '', transaction_reference: '', notes: '', attachments: [] as File[] });
    const disburseForm = useForm({ transfer_date: '', transferred_at: '', payment_method: 'bank_transfer', bank_name: '', source_account_name: '', source_account_number: '', transaction_reference: '', notes: '', attachments: [] as File[] });
    const rejectForm = useForm({ rejection_reason: '', notes: '' });
    const revisionForm = useForm({ revision_subject: '', revision_message: '', revision_fields: ['other'] as string[], notes: '' });
    const revisionFields = ['approval_amount', 'approval_notes', 'finance_review', 'submission_amount', 'category', 'submission_type', 'bank_account', 'attachment', 'needed_date', 'other'];
    const destination = `${submission.bank_name_snapshot ?? '-'} - ${submission.bank_account_holder_snapshot ?? '-'} - ${submission.bank_account_number_snapshot ?? '-'}`;

    const files = (event: ChangeEvent<HTMLInputElement>) => Array.from(event.target.files ?? []);

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p className="text-sm text-muted-foreground">{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? '-'}</div><div>Kategori: {submission.request_category?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? '-'}</div><div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div>Nominal PIC: {rupiah(submission.total_amount)}</div><div>Nominal Finance: {rupiah(submission.finance_detail?.validated_total_amount ?? submission.total_amount)}</div>
            <div>Nominal Approval: {rupiah(submission.approval_approved_amount)}</div><div>Nominal Director: {submission.director_approved_amount ? rupiah(submission.director_approved_amount) : '-'}</div>
            <div>Catatan approval: {approvalReview?.notes ?? '-'}</div><div>Rekening tujuan: {destination}</div>
        </div>

        {submission.status === 'director_review' && <form onSubmit={(event) => { event.preventDefault(); startForm.post(`/director/submissions/${submission.id}/start-review`); }}>
            <Button disabled={startForm.processing}>{startForm.processing ? 'Memulai...' : 'Mulai Review'}</Button>
        </form>}

        {submission.status === 'director_in_review' && <div className="grid gap-4 lg:grid-cols-2">
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); pendingForm.post(`/director/submissions/${submission.id}/approve-pending-disbursement`); }}>
                <h2 className="font-semibold">Setujui - Bayar Nanti</h2><Label>Nominal disetujui</Label><Input type="number" value={pendingForm.data.approved_amount} onChange={(event) => pendingForm.setData('approved_amount', event.target.value)} /><Label>Catatan Director</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={pendingForm.data.notes} onChange={(event) => pendingForm.setData('notes', event.target.value)} /><Button disabled={pendingForm.processing}>Setujui dan Masuk Antrean Pencairan</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); disburseNowForm.post(`/director/submissions/${submission.id}/approve-and-disburse`, { forceFormData: true }); }}>
                <h2 className="font-semibold">Setujui dan Kirim Dana</h2><p className="text-sm text-muted-foreground">Rekening tujuan: {destination}</p><Label>Nominal disetujui</Label><Input type="number" value={disburseNowForm.data.approved_amount} onChange={(event) => disburseNowForm.setData('approved_amount', event.target.value)} /><PaymentFields form={disburseNowForm} onFiles={(value: File[]) => disburseNowForm.setData('attachments', value)} files={files} /><Button disabled={disburseNowForm.processing}>Setujui dan Kirim Dana</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); revisionForm.post(`/director/submissions/${submission.id}/request-revision`); }}>
                <h2 className="font-semibold">Minta Revisi ke Finance Approval</h2><Input placeholder="Judul revisi" value={revisionForm.data.revision_subject} onChange={(event) => revisionForm.setData('revision_subject', event.target.value)} /><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" placeholder="Pesan revisi" value={revisionForm.data.revision_message} onChange={(event) => revisionForm.setData('revision_message', event.target.value)} /><div className="flex flex-wrap gap-2 text-xs">{revisionFields.map((field) => <label key={field} className="flex items-center gap-1"><input type="checkbox" checked={revisionForm.data.revision_fields.includes(field)} onChange={(event) => revisionForm.setData('revision_fields', event.target.checked ? [...revisionForm.data.revision_fields, field] : revisionForm.data.revision_fields.filter((value: string) => value !== field))} />{field}</label>)}</div><Button variant="outline" disabled={revisionForm.processing}>Minta Revisi</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); rejectForm.post(`/director/submissions/${submission.id}/reject`); }}>
                <h2 className="font-semibold">Tolak Pengajuan</h2><Label>Alasan penolakan</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={rejectForm.data.rejection_reason} onChange={(event) => rejectForm.setData('rejection_reason', event.target.value)} /><Label>Catatan tambahan</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={rejectForm.data.notes} onChange={(event) => rejectForm.setData('notes', event.target.value)} /><Button variant="outline" disabled={rejectForm.processing}>Tolak</Button>
            </form>
        </div>}

        {submission.status === 'pending_disbursement' && <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); disburseForm.post(`/director/submissions/${submission.id}/disburse`, { forceFormData: true }); }}>
            <h2 className="font-semibold">Kirim Dana</h2><p className="text-sm text-muted-foreground">Nominal pencairan: {rupiah(submission.director_approved_amount)}. Rekening tujuan: {destination}</p><PaymentFields form={disburseForm} onFiles={(value: File[]) => disburseForm.setData('attachments', value)} files={files} /><Button disabled={disburseForm.processing}>Kirim Dana</Button>
        </form>}

        {submission.status === 'director_revision_requested' && <div className="rounded-md border p-4 text-sm">Menunggu perbaikan Finance Approval. Revisi: {directorReview?.revision_subject ?? '-'}</div>}
        {submission.status === 'fund_disbursed' && <div className="rounded-md border p-4 text-sm">Dana sudah dikirim: {rupiah(submission.disbursed_amount)} pada {formatDate(submission.disbursed_at)}</div>}
        {submission.status === 'director_rejected' && <div className="rounded-md border p-4 text-sm">Pengajuan ditolak Director. Alasan: {directorReview?.rejection_reason ?? '-'}</div>}

        {submission.disbursement && <div className="space-y-2 rounded-md border p-4 text-sm"><h2 className="font-semibold">Detail Pencairan</h2><div>Nomor: {submission.disbursement.disbursement_number}</div><div>Metode: {submission.disbursement.payment_method}</div><div>Referensi: {submission.disbursement.transaction_reference ?? '-'}</div><div>Bukti: {(submission.disbursement.attachments ?? []).map((attachment: any) => <a key={attachment.id} className="mr-3 underline" href={`/director/disbursement-attachments/${attachment.id}/download`}>{attachment.original_name}</a>)}</div></div>}
        <SubmissionAttachments submission={submission} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}

function PaymentFields({ form, onFiles, files }: any) {
    return <>
        <Label>Tanggal transfer</Label><Input type="date" value={form.data.transfer_date} onChange={(event) => form.setData('transfer_date', event.target.value)} />
        <Label>Waktu transfer</Label><Input type="datetime-local" value={form.data.transferred_at} onChange={(event) => form.setData('transferred_at', event.target.value)} />
        <Label>Metode pembayaran</Label><Select value={form.data.payment_method} onValueChange={(value) => form.setData('payment_method', value)}><SelectTrigger><SelectValue placeholder="Pilih metode" /></SelectTrigger><SelectContent><SelectItem value="bank_transfer">Bank Transfer</SelectItem><SelectItem value="cash">Cash</SelectItem><SelectItem value="virtual_account">Virtual Account</SelectItem><SelectItem value="other">Other</SelectItem></SelectContent></Select>
        <Label>Bank sumber</Label><Input value={form.data.bank_name} onChange={(event) => form.setData('bank_name', event.target.value)} />
        <Label>Nama rekening sumber</Label><Input value={form.data.source_account_name} onChange={(event) => form.setData('source_account_name', event.target.value)} />
        <Label>Nomor rekening sumber</Label><Input value={form.data.source_account_number} onChange={(event) => form.setData('source_account_number', event.target.value)} />
        <Label>Nomor referensi</Label><Input value={form.data.transaction_reference} onChange={(event) => form.setData('transaction_reference', event.target.value)} />
        <Label>Catatan</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
        <Label>Bukti transfer</Label><Input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(event) => onFiles(files(event))} />
    </>;
}
