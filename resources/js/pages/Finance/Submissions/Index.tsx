import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

export default function FinanceSubmissionsIndex({ submissions, filters }: any) {
    return <div className="space-y-4 p-4"><Head title="Pengajuan Masuk" /><h1 className="text-2xl font-semibold">Pengajuan Masuk</h1>
        <form onSubmit={(e) => { e.preventDefault(); router.get('/finance/submissions', Object.fromEntries(new FormData(e.currentTarget)), { preserveState: true }); }} className="flex gap-2"><Input name="search" defaultValue={filters.search ?? ''} placeholder="Cari" /><Button>Filter</Button></form>
        <div className="overflow-hidden rounded-md border"><table className="w-full text-sm"><tbody>{submissions.data.map((s: any) => <tr key={s.id} className="border-b"><td className="p-3">{s.submission_number}</td><td>{s.cooperative?.name}</td><td>{s.submitter?.name}</td><td>{rupiah(s.total_amount)}</td><td><SubmissionStatusBadge status={s.status} /></td><td className="p-3 text-right"><Button size="sm" variant="outline" asChild><Link href={`/finance/submissions/${s.id}`}>Detail</Link></Button>{s.status === 'submitted' && <Button size="sm" onClick={() => router.post(`/finance/submissions/${s.id}/start-review`)}>Mulai Review</Button>}</td></tr>)}</tbody></table></div>
    </div>;
}
