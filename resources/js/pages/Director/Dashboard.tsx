import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function DirectorDashboard({ summary, actionable }: any) {
    const cards = [
        ['Menunggu Review', summary.waiting_review],
        ['Sedang Direview', summary.in_review],
        ['Menunggu Revisi Approval', summary.revision_requested],
        ['Menunggu Pencairan', summary.pending_disbursement],
        ['Dana Terkirim Hari Ini', summary.today_disbursed],
        ['Total Pending', rupiah(summary.pending_disbursement_amount)],
        ['Terkirim Bulan Ini', rupiah(summary.month_disbursed_amount)],
    ];

    return <div className="space-y-4 p-4"><Head title="Dashboard Director" /><h1 className="text-2xl font-semibold">Dashboard Finance Director</h1>
        <div className="grid gap-3 md:grid-cols-3 xl:grid-cols-4">{cards.map(([label, value]) => <div key={label} className="rounded-md border p-4"><div className="text-sm text-muted-foreground">{label}</div><div className="mt-2 text-2xl font-semibold">{value}</div></div>)}</div>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Koperasi</TableHead><TableHead>Status</TableHead><TableHead>Tanggal Dibutuhkan</TableHead><TableHead>Nominal</TableHead><TableHead /></TableRow></TableHeader><TableBody>{actionable.map((s: any) => <TableRow key={s.id}><TableCell>{s.submission_number}</TableCell><TableCell>{s.cooperative?.name}</TableCell><TableCell><SubmissionStatusBadge status={s.status} /></TableCell><TableCell>{formatDate(s.needed_date)}</TableCell><TableCell>{rupiah(s.director_approved_amount ?? s.approval_approved_amount ?? s.total_amount)}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/director/submissions/${s.id}`}>Buka</Link></Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
