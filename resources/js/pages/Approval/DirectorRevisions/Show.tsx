import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';

export default function ApprovalDirectorRevisionsShow({ submission }: any) {
    const directorReview = submission.director_reviews?.[0];
    const approvalReview = submission.approval_reviews?.[0];
    const resubmitForm = useForm({ change_summary: '', notes: '' });

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2"><div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div><div>Kategori: {submission.request_category?.name}</div><div>Jenis: {submission.request_type?.name}</div><div>Nominal Approval: {rupiah(submission.approval_approved_amount)}</div><div>Catatan Approval: {approvalReview?.notes ?? '-'}</div></div>
        <div className="space-y-2 rounded-md border p-4 text-sm"><h2 className="font-semibold">Revisi Finance Director</h2><div>Judul: {directorReview?.revision_subject ?? '-'}</div><div>Pesan: {directorReview?.revision_message ?? '-'}</div><div>Field: {(directorReview?.revision_fields ?? []).join(', ')}</div><div>Catatan internal: {directorReview?.notes ?? '-'}</div></div>
        <form className="space-y-3 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); resubmitForm.post(`/approval/director-revisions/${submission.id}/resubmit`); }}>
            <h2 className="font-semibold">Kirim Ulang ke Director</h2><Label>Ringkasan perubahan</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={resubmitForm.data.change_summary} onChange={(event) => resubmitForm.setData('change_summary', event.target.value)} /><Label>Catatan kepada Director</Label><Input value={resubmitForm.data.notes} onChange={(event) => resubmitForm.setData('notes', event.target.value)} /><Button disabled={resubmitForm.processing}>Resubmit ke Director</Button>
        </form>
        <SubmissionAttachments submission={submission} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
