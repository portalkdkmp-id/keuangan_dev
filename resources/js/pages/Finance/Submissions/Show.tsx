import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionItemRow } from '@/components/Submissions/SubmissionItemRow';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';

export default function FinanceSubmissionsShow({ submission }: any) {
    return <div className="space-y-4 p-4"><Head title={submission.submission_number} /><div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><SubmissionStatusBadge status={submission.status} /></div>
        <div className="rounded-md border p-4 text-sm">{submission.cooperative?.name} · {submission.submitter?.name}</div>
        <table className="w-full text-sm"><tbody>{submission.items.map((item: any) => <SubmissionItemRow key={item.id} item={item} />)}</tbody></table>
        <div className="text-right text-xl font-semibold">{rupiah(submission.total_amount)}</div>
        <SubmissionAttachments submission={submission} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
        {submission.status === 'submitted' && <Button onClick={() => router.post(`/finance/submissions/${submission.id}/start-review`)}>Mulai Review</Button>}
    </div>;
}
