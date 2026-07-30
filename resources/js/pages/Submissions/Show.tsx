import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function SubmissionsShow({ submission }: any) {
    const isDraft = submission.status === 'draft';
    const needsRevision = submission.status === 'revision_requested';
    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>Koperasi: {submission.cooperative?.name}</div>
            <div>Pengaju: {submission.submitter?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? submission.submitterCity?.name ?? '-'}</div>
            <div>Kategori: {submission.request_category?.name ?? submission.requestCategory?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? submission.requestType?.name ?? '-'}</div>
            <div>Rekening: {submission.recipient_bank_account ? `${submission.recipient_bank_account.bank_name} - ${submission.recipient_bank_account.account_holder_name}` : '-'}</div>
            <div>Tanggal diajukan: {formatDate(submission.created_at)}</div>
            <div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div className="font-semibold md:col-span-2">Nominal: {rupiah(submission.total_amount)}</div>
        </div>
        <p className="rounded-md border p-4 text-sm">{submission.purpose}</p>
        <SubmissionAttachments submission={submission} editable={isDraft} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
        {isDraft && <div className="flex gap-2"><Button asChild><Link href={`/submissions/${submission.id}/edit`}>Edit</Link></Button><Button onClick={() => router.post(`/submissions/${submission.id}/submit`)}>Kirim Pengajuan</Button><Button variant="outline" onClick={() => router.delete(`/submissions/${submission.id}`)}>Hapus Draft</Button></div>}
        {needsRevision && <Button asChild><Link href={`/submissions/${submission.id}/revision`}>Buka Revisi</Link></Button>}
    </div>;
}
