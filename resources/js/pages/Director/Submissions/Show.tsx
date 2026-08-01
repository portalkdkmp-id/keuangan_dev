import { Head, router, useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { BackButton } from '@/components/back-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';

function normalizeDateTime(value?: string | null): string {
    if (!value) {
        return '';
    }

    const normalized = value.replace('T', ' ');

    return normalized.length === 16 ? `${normalized}:00` : normalized;
}

function transferDate(value?: string | null): string {
    return value ? value.slice(0, 10) : '';
}

export default function DirectorSubmissionsShow({ submission, sourceBankAccounts = [] }: any) {
    const directorReview = submission.director_reviews?.[0];
    const approvalReview = submission.approval_reviews?.[0];
    const [accountDialogOpen, setAccountDialogOpen] = useState(false);
    const startForm = useForm({});
    const pendingForm = useForm({ approved_amount: submission.approval_approved_amount ?? '', notes: '' });
    const disburseNowForm = useForm({ approved_amount: submission.approval_approved_amount ?? '', transfer_date: '', transferred_at: '', payment_method: 'bank_transfer', bank_name: '', source_account_name: '', source_account_number: '', transaction_reference: '', notes: '', attachments: [] as File[] });
    const disburseForm = useForm({ transfer_date: '', transferred_at: '', payment_method: 'bank_transfer', bank_name: '', source_account_name: '', source_account_number: '', transaction_reference: '', notes: '', attachments: [] as File[] });
    const rejectForm = useForm({ rejection_reason: '' });
    const revisionForm = useForm({ revision_message: '' });
    const accountForm = useForm({ bank_name: '', account_number: '', account_holder_name: '', is_active: true, is_primary: false });
    const destination = `${submission.bank_name_snapshot ?? '-'} - ${submission.bank_account_holder_snapshot ?? '-'} - ${submission.bank_account_number_snapshot ?? '-'}`;

    const files = (event: ChangeEvent<HTMLInputElement>) => Array.from(event.target.files ?? []);
    const submitDisburseNow = () => {
        disburseNowForm.transform((data) => ({ ...data, transfer_date: transferDate(data.transferred_at), transferred_at: normalizeDateTime(data.transferred_at) }));
        disburseNowForm.post(`/director/submissions/${submission.id}/approve-and-disburse`, { forceFormData: true });
    };
    const submitDisburse = () => {
        disburseForm.transform((data) => ({ ...data, transfer_date: transferDate(data.transferred_at), transferred_at: normalizeDateTime(data.transferred_at) }));
        disburseForm.post(`/director/submissions/${submission.id}/disburse`, { forceFormData: true });
    };
    const submitAccount = () => accountForm.post('/bank-accounts/quick-store', {
        preserveScroll: true,
        onSuccess: () => {
            accountForm.reset();
            setAccountDialogOpen(false);
            router.reload({ only: ['sourceBankAccounts'] });
        },
    });

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} /><BackButton fallback="/director/submissions" />
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p className="text-sm text-muted-foreground">{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? '-'}</div><div>Kategori: {submission.request_category?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? '-'}</div><div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div>Nominal PIC: {rupiah(submission.total_amount)}</div><div>Nominal Finance: {rupiah(submission.finance_detail?.validated_total_amount ?? submission.total_amount)}</div>
            <div>Nominal Approval: {rupiah(submission.approval_approved_amount)}</div><div>Nominal Director: {submission.director_approved_amount ? rupiah(submission.director_approved_amount) : '-'}</div>
            <div>Catatan approval: {approvalReview?.notes ?? '-'}</div><div>Rekening tujuan: {destination}</div>
        </div>

        <SubmissionAttachments submission={submission} />

        {submission.status === 'director_review' && <form onSubmit={(event) => { event.preventDefault(); startForm.post(`/director/submissions/${submission.id}/start-review`); }}>
            <Button disabled={startForm.processing}>{startForm.processing ? 'Memulai...' : 'Mulai Review'}</Button>
        </form>}

        {submission.status === 'director_in_review' && <div className="grid gap-4 lg:grid-cols-2">
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); pendingForm.post(`/director/submissions/${submission.id}/approve-pending-disbursement`); }}>
                <h2 className="font-semibold">Setujui - Bayar Nanti</h2><Label>Nominal disetujui</Label><MoneyInput value={pendingForm.data.approved_amount} onChange={(value) => pendingForm.setData('approved_amount', value)} /><Label>Catatan Director</Label><Textarea className="min-h-24" value={pendingForm.data.notes} onChange={(event) => pendingForm.setData('notes', event.target.value)} /><Button disabled={pendingForm.processing}>Setujui dan Masuk Antrean Pencairan</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); submitDisburseNow(); }}>
                <h2 className="font-semibold">Setujui dan Kirim Dana</h2><p className="text-sm text-muted-foreground">Rekening tujuan: {destination}</p><Label>Nominal disetujui</Label><MoneyInput value={disburseNowForm.data.approved_amount} onChange={(value) => disburseNowForm.setData('approved_amount', value)} /><PaymentFields form={disburseNowForm} sourceBankAccounts={sourceBankAccounts} onAddAccount={() => setAccountDialogOpen(true)} onFiles={(value: File[]) => disburseNowForm.setData('attachments', value)} files={files} /><Button disabled={disburseNowForm.processing}>Setujui dan Kirim Dana</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); revisionForm.post(`/director/submissions/${submission.id}/request-revision`); }}>
                <h2 className="font-semibold">Minta Revisi ke Finance Approval</h2><Label>Catatan Revisi</Label><Textarea className="min-h-24" value={revisionForm.data.revision_message} onChange={(event) => revisionForm.setData('revision_message', event.target.value)} /><Button variant="outline" disabled={revisionForm.processing}>Minta Revisi</Button>
            </form>
            <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); rejectForm.post(`/director/submissions/${submission.id}/reject`); }}>
                <h2 className="font-semibold">Tolak Pengajuan</h2><Label>Catatan Penolakan</Label><Textarea className="min-h-24" value={rejectForm.data.rejection_reason} onChange={(event) => rejectForm.setData('rejection_reason', event.target.value)} /><Button variant="outline" disabled={rejectForm.processing}>Tolak</Button>
            </form>
        </div>}

        {submission.status === 'pending_disbursement' && <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); submitDisburse(); }}>
            <h2 className="font-semibold">Kirim Dana</h2><p className="text-sm text-muted-foreground">Nominal pencairan: {rupiah(submission.director_approved_amount)}. Rekening tujuan: {destination}</p><PaymentFields form={disburseForm} sourceBankAccounts={sourceBankAccounts} onAddAccount={() => setAccountDialogOpen(true)} onFiles={(value: File[]) => disburseForm.setData('attachments', value)} files={files} /><Button disabled={disburseForm.processing}>Kirim Dana</Button>
        </form>}

        {submission.status === 'director_revision_requested' && <div className="rounded-md border p-4 text-sm">Menunggu perbaikan Finance Approval. Revisi: {directorReview?.revision_subject ?? '-'}</div>}
        {submission.status === 'fund_disbursed' && <div className="rounded-md border p-4 text-sm">Dana sudah dikirim: {rupiah(submission.disbursed_amount)} pada {formatDate(submission.disbursed_at)}</div>}
        {submission.status === 'director_rejected' && <div className="rounded-md border p-4 text-sm">Pengajuan ditolak Director. Alasan: {directorReview?.rejection_reason ?? '-'}</div>}

        {submission.disbursement && <div className="space-y-2 rounded-md border p-4 text-sm"><h2 className="font-semibold">Detail Pencairan</h2><div>Nomor: {submission.disbursement.disbursement_number}</div><div>Metode: {submission.disbursement.payment_method}</div><div>Referensi: {submission.disbursement.transaction_reference ?? '-'}</div><div>Bukti: {(submission.disbursement.attachments ?? []).map((attachment: any) => <a key={attachment.id} className="mr-3 underline" href={`/director/disbursement-attachments/${attachment.id}/download`}>{attachment.original_name}</a>)}</div></div>}
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
        <Dialog open={accountDialogOpen} onOpenChange={setAccountDialogOpen}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tambah Rekening Sumber</DialogTitle>
                    <DialogDescription>Rekening ini akan tersimpan sebagai rekening user yang sedang login.</DialogDescription>
                </DialogHeader>
                <form className="space-y-3" onSubmit={(event) => { event.preventDefault(); submitAccount(); }}>
                    <div><Label>Nama bank</Label><Input value={accountForm.data.bank_name} onChange={(event) => accountForm.setData('bank_name', event.target.value)} /></div>
                    <div><Label>Nomor rekening</Label><Input value={accountForm.data.account_number} onChange={(event) => accountForm.setData('account_number', event.target.value)} /></div>
                    <div><Label>Nama pada rekening</Label><Input value={accountForm.data.account_holder_name} onChange={(event) => accountForm.setData('account_holder_name', event.target.value)} /></div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setAccountDialogOpen(false)}>Batal</Button>
                        <Button disabled={accountForm.processing}>Simpan Rekening</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>;
}

function PaymentFields({ form, sourceBankAccounts, onAddAccount, onFiles, files }: any) {
    const chooseAccount = (id: string) => {
        const account = sourceBankAccounts.find((item: any) => item.id === id);

        if (!account) {
            return;
        }

        form.setData({
            ...form.data,
            bank_name: account.bank_name,
            source_account_name: account.account_holder_name,
            source_account_number: account.account_number,
        });
    };
    const selectedAccount = sourceBankAccounts.find((account: any) => account.bank_name === form.data.bank_name && account.account_holder_name === form.data.source_account_name && account.account_number === form.data.source_account_number);

    return <>
        <Label>Waktu transfer</Label><Input type="datetime-local" value={form.data.transferred_at} onChange={(event) => form.setData('transferred_at', event.target.value)} />
        <Label>Metode pembayaran</Label><Select value={form.data.payment_method} onValueChange={(value) => form.setData('payment_method', value)}><SelectTrigger><SelectValue placeholder="Pilih metode" /></SelectTrigger><SelectContent><SelectItem value="bank_transfer">Bank Transfer</SelectItem><SelectItem value="cash">Cash</SelectItem><SelectItem value="virtual_account">Virtual Account</SelectItem><SelectItem value="other">Other</SelectItem></SelectContent></Select>
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <Label>Rekening sumber</Label>
                <Button type="button" size="sm" variant="outline" onClick={onAddAccount}>Tambah Rekening</Button>
            </div>
            {sourceBankAccounts.length ? <Select value={selectedAccount?.id} onValueChange={chooseAccount}><SelectTrigger><SelectValue placeholder="Pilih rekening sumber" /></SelectTrigger><SelectContent>{sourceBankAccounts.map((account: any) => <SelectItem key={account.id} value={account.id}>{account.bank_name} - {account.account_holder_name} - {account.account_number}</SelectItem>)}</SelectContent></Select> : <div className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">Belum ada rekening sumber. Tambahkan rekening terlebih dahulu.</div>}
        </div>
        <Label>Nomor referensi</Label><Input value={form.data.transaction_reference} onChange={(event) => form.setData('transaction_reference', event.target.value)} />
        <Label>Catatan</Label><Textarea className="min-h-20" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
        <Label>Bukti transfer</Label><Input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(event) => onFiles(files(event))} />
    </>;
}
