import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionItemRow } from '@/components/Submissions/SubmissionItemRow';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';

export default function SubmissionsShow({ submission }: any) {
    const isDraft = submission.status === 'draft';
    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="rounded-md border p-4 text-sm">{submission.cooperative?.name} · {submission.submitter?.name}</div>
        <p className="rounded-md border p-4 text-sm">{submission.purpose}</p>
        <table className="w-full text-sm"><tbody>{submission.items.map((item: any) => <SubmissionItemRow key={item.id} item={item} />)}</tbody></table>
        <div className="text-right text-xl font-semibold">{rupiah(submission.total_amount)}</div>
        <SubmissionAttachments submission={submission} editable={isDraft} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
        {isDraft && <div className="flex gap-2"><Button asChild><Link href={`/submissions/${submission.id}/edit`}>Edit</Link></Button><Button onClick={() => router.post(`/submissions/${submission.id}/submit`)}>Kirim Pengajuan</Button><Button variant="outline" onClick={() => router.delete(`/submissions/${submission.id}`)}>Hapus Draft</Button></div>}
    </div>;
}
