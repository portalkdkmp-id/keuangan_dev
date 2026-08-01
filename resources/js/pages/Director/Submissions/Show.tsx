import { Head } from '@inertiajs/react';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function DirectorSubmissionsShow({ submission }: any) {
    const review = submission.approval_reviews?.[0];
    return <div className="space-y-4 p-4"><Head title={submission.submission_number} /><div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><SubmissionStatusBadge status={submission.status} /></div>
        <div className="rounded-md border p-4 text-sm">Fitur keputusan Finance Director akan tersedia pada Phase berikutnya.</div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2"><div>Title: {submission.title}</div><div>PIC: {submission.submitter?.name}</div><div>Koperasi: {submission.cooperative?.name}</div><div>Kategori: {submission.request_category?.name}</div><div>Jenis: {submission.request_type?.name}</div><div>Approved Amount: {rupiah(submission.approval_approved_amount)}</div><div>Approver: {submission.approval_decision_maker?.name}</div><div>Tanggal Approval: {formatDate(submission.approval_decided_at)}</div><div>Catatan Approval: {review?.notes ?? '-'}</div></div>
        <SubmissionAttachments submission={submission} /><SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
