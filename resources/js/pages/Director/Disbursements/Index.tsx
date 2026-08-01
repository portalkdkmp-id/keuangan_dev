import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function DirectorDisbursementsIndex({ disbursements }: any) {
    return <div className="space-y-4 p-4"><Head title="Pencairan Dana" /><h1 className="text-2xl font-semibold">Pencairan Dana</h1>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Pengajuan</TableHead><TableHead>Koperasi</TableHead><TableHead>PIC</TableHead><TableHead>Nominal</TableHead><TableHead>Tanggal Transfer</TableHead><TableHead /></TableRow></TableHeader><TableBody>{disbursements.data.map((d: any) => <TableRow key={d.id}><TableCell>{d.disbursement_number}</TableCell><TableCell>{d.submission?.submission_number}</TableCell><TableCell>{d.submission?.cooperative?.name}</TableCell><TableCell>{d.submission?.submitter?.name}</TableCell><TableCell>{rupiah(d.amount)}</TableCell><TableCell>{formatDate(d.transferred_at)}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/director/disbursements/${d.id}`}>Detail</Link></Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
