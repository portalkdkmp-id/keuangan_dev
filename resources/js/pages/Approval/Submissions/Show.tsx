import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function ApprovalSubmissionsShow({ submission }: any) {
    const review = submission.active_approval_review ?? submission.activeApprovalReview ?? submission.approval_reviews?.[0];
    const approveForm = useForm({ approved_amount: review?.submitted_amount ?? submission.total_amount, notes: '' });
    const rejectForm = useForm({ rejection_reason: '', notes: '' });
    const revisionForm = useForm({ revision_subject: '', revision_message: '', revision_fields: ['other'] as string[], notes: '' });
    const fields = ['title', 'category', 'submission_type', 'amount', 'needed_date', 'pic_notes', 'finance_notes', 'bank_account', 'attachment', 'cooperative', 'other'];

    const toggleField = (field: string, checked: boolean) => revisionForm.setData('revision_fields', checked ? [...revisionForm.data.revision_fields, field] : revisionForm.data.revision_fields.filter((value) => value !== field));

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? '-'}</div><div>Kategori: {submission.request_category?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? '-'}</div><div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div>Nominal finance: {rupiah(review?.submitted_amount ?? submission.total_amount)}</div><div>Catatan finance: {submission.finance_detail?.finance_notes ?? '-'}</div>
            <div>Rekening snapshot: {submission.bank_name_snapshot ?? submission.recipient_bank_account?.bank_name ?? '-'} - {submission.bank_account_holder_snapshot ?? submission.recipient_bank_account?.account_holder_name ?? '-'}</div>
            <div>Nomor rekening: {submission.bank_account_number_snapshot ?? submission.recipient_bank_account?.account_number ?? '-'}</div>
        </div>
        {submission.status === 'approval_review' && <Button onClick={() => router.post(`/approval/submissions/${submission.id}/start-review`)}>Mulai Review</Button>}
        {submission.status === 'approval_in_review' && <div className="grid gap-4 lg:grid-cols-3">
            <form onSubmit={(e) => { e.preventDefault(); approveForm.post(`/approval/submissions/${submission.id}/approve`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Setujui</h2><Label>Nominal disetujui</Label><Input type="number" value={approveForm.data.approved_amount} onChange={(e) => approveForm.setData('approved_amount', e.target.value)} /><Label>Catatan approval</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={approveForm.data.notes} onChange={(e) => approveForm.setData('notes', e.target.value)} /><Button>Setujui dan Kirim ke Director</Button>
            </form>
            <form onSubmit={(e) => { e.preventDefault(); revisionForm.post(`/approval/submissions/${submission.id}/request-revision`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Minta Revisi</h2><Input placeholder="Judul revisi" value={revisionForm.data.revision_subject} onChange={(e) => revisionForm.setData('revision_subject', e.target.value)} /><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" placeholder="Pesan revisi" value={revisionForm.data.revision_message} onChange={(e) => revisionForm.setData('revision_message', e.target.value)} /><div className="flex flex-wrap gap-2 text-xs">{fields.map((field) => <label key={field} className="flex items-center gap-1"><Checkbox checked={revisionForm.data.revision_fields.includes(field)} onCheckedChange={(checked) => toggleField(field, Boolean(checked))} />{field}</label>)}</div><Button variant="outline">Minta Revisi</Button>
            </form>
            <form onSubmit={(e) => { e.preventDefault(); rejectForm.post(`/approval/submissions/${submission.id}/reject`); }} className="space-y-3 rounded-md border p-4">
                <h2 className="font-semibold">Tolak</h2><Label>Alasan penolakan</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={rejectForm.data.rejection_reason} onChange={(e) => rejectForm.setData('rejection_reason', e.target.value)} /><Label>Catatan tambahan</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={rejectForm.data.notes} onChange={(e) => rejectForm.setData('notes', e.target.value)} /><Button variant="outline">Tolak Pengajuan</Button>
            </form>
        </div>}
        {submission.status === 'approval_revision_requested' && <div className="rounded-md border p-4 text-sm">Menunggu perbaikan Finance Staff.</div>}
        {submission.status === 'director_review' && <div className="rounded-md border p-4 text-sm">Sudah disetujui dan diteruskan ke Finance Director. Nominal disetujui: {rupiah(submission.approval_approved_amount)}</div>}
        {submission.status === 'approval_rejected' && <div className="rounded-md border p-4 text-sm">Pengajuan ditolak. Alasan: {review?.rejection_reason ?? '-'}</div>}
        <SubmissionAttachments submission={submission} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
