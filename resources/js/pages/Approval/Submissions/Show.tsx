import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { BackButton } from '@/components/back-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';

export default function ApprovalSubmissionsShow({ submission }: any) {
    const review = submission.active_approval_review ?? submission.activeApprovalReview ?? submission.approval_reviews?.[0];
    const startReviewForm = useForm({});
    const approveForm = useForm({ approved_amount: review?.submitted_amount ?? submission.total_amount, notes: '' });
    const rejectForm = useForm({ rejection_reason: '', notes: '' });
    const revisionForm = useForm({ revision_message: '' });

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} /><BackButton fallback="/approval/submissions" />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? '-'}</div><div>Kategori: {submission.request_category?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? '-'}</div><div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div>Nominal finance: {rupiah(review?.submitted_amount ?? submission.total_amount)}</div><div>Catatan finance: {submission.finance_detail?.finance_notes ?? '-'}</div>
            <div>Rekening snapshot: {submission.bank_name_snapshot ?? submission.recipient_bank_account?.bank_name ?? '-'} - {submission.bank_account_holder_snapshot ?? submission.recipient_bank_account?.account_holder_name ?? '-'}</div>
            <div>Nomor rekening: {submission.bank_account_number_snapshot ?? submission.recipient_bank_account?.account_number ?? '-'}</div>
        </div>
        <SubmissionAttachments submission={submission} />
        {submission.status === 'approval_review' && <form onSubmit={(event) => {
            event.preventDefault();
            startReviewForm.post(`/approval/submissions/${submission.id}/start-review`, { preserveScroll: true });
        }}>
            <Button type="submit" disabled={startReviewForm.processing}>{startReviewForm.processing ? 'Memulai...' : 'Mulai Review'}</Button>
        </form>}
        {submission.status === 'approval_in_review' && <div className="grid gap-4 lg:grid-cols-3">
            <form onSubmit={(e) => { e.preventDefault(); approveForm.post(`/approval/submissions/${submission.id}/approve`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Setujui</h2><Label>Nominal disetujui</Label><MoneyInput value={approveForm.data.approved_amount} onChange={(value) => approveForm.setData('approved_amount', value)} /><Label>Catatan approval</Label><Textarea className="min-h-24" value={approveForm.data.notes} onChange={(e) => approveForm.setData('notes', e.target.value)} /><Button>Setujui dan Kirim ke Director</Button>
            </form>
            <form onSubmit={(e) => { e.preventDefault(); revisionForm.post(`/approval/submissions/${submission.id}/request-revision`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Minta Revisi</h2><Label>Catatan Revisi</Label><Textarea className="min-h-24" value={revisionForm.data.revision_message} onChange={(e) => revisionForm.setData('revision_message', e.target.value)} /><Button variant="outline">Minta Revisi</Button>
            </form>
            <form onSubmit={(e) => { e.preventDefault(); rejectForm.post(`/approval/submissions/${submission.id}/reject`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Tolak</h2><Label>Alasan Penolakan</Label><Textarea className="min-h-24" value={rejectForm.data.rejection_reason} onChange={(e) => rejectForm.setData('rejection_reason', e.target.value)} /><Button variant="outline">Tolak Pengajuan</Button>
            </form>
        </div>}
        {submission.status === 'approval_revision_requested' && <div className="rounded-md border p-4 text-sm">Menunggu perbaikan Finance Staff.</div>}
        {submission.status === 'director_review' && <div className="rounded-md border p-4 text-sm">Sudah disetujui dan diteruskan ke Finance Director. Nominal disetujui: {rupiah(submission.approval_approved_amount)}</div>}
        {submission.status === 'approval_rejected' && <div className="rounded-md border p-4 text-sm">Pengajuan ditolak. Alasan: {review?.rejection_reason ?? '-'}</div>}
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
