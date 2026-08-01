import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function DirectorSubmissionsIndex({ submissions }: any) {
    return <div className="space-y-4 p-4"><Head title="Director Review" /><h1 className="text-2xl font-semibold">Pengajuan Menunggu Director</h1>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Title</TableHead><TableHead>PIC</TableHead><TableHead>Koperasi</TableHead><TableHead>Approved</TableHead><TableHead>Tanggal Approval</TableHead><TableHead /></TableRow></TableHeader><TableBody>{submissions.data.map((s: any) => <TableRow key={s.id}><TableCell>{s.submission_number}</TableCell><TableCell>{s.title}</TableCell><TableCell>{s.submitter?.name}</TableCell><TableCell>{s.cooperative?.name}</TableCell><TableCell>{rupiah(s.approval_approved_amount)}</TableCell><TableCell>{formatDate(s.approval_decided_at)}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/director/submissions/${s.id}`}>Detail</Link></Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
