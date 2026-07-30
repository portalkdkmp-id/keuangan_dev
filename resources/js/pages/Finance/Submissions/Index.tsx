import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function FinanceSubmissionsIndex({ submissions, filters }: any) {
    return <div className="space-y-4 p-4"><Head title="Pengajuan Masuk" /><h1 className="text-2xl font-semibold">Pengajuan Masuk</h1>
        <form onSubmit={(e) => { e.preventDefault(); router.get('/finance/submissions', Object.fromEntries(new FormData(e.currentTarget)), { preserveState: true }); }} className="flex gap-2"><Input name="search" defaultValue={filters.search ?? ''} placeholder="Cari" /><Button>Filter</Button></form>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Koperasi</TableHead><TableHead>PIC</TableHead><TableHead>Tanggal</TableHead><TableHead>Total</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader><TableBody>{submissions.data.map((s: any) => <TableRow key={s.id}><TableCell>{s.submission_number}</TableCell><TableCell>{s.cooperative?.name}</TableCell><TableCell>{s.submitter?.name}</TableCell><TableCell>{formatDate(s.created_at)}</TableCell><TableCell>{rupiah(s.total_amount)}</TableCell><TableCell><SubmissionStatusBadge status={s.status} /></TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/finance/submissions/${s.id}`}>Detail</Link></Button>{s.status === 'submitted' && <Button size="sm" onClick={() => router.post(`/finance/submissions/${s.id}/start-review`)}>Mulai Review</Button>}</TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
