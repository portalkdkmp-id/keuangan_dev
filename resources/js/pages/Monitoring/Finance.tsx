import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

export default function FinanceMonitoring({ stats, needsAction }: any) {
    return <div className="space-y-4 p-4"><Head title="Dashboard Finance" /><h1 className="text-2xl font-semibold">Dashboard Finance</h1><div className="grid gap-3 md:grid-cols-4">{Object.entries(stats).map(([key, value]: any) => <Card key={key}><CardHeader><CardTitle>{key}</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{key.includes('amount') ? rupiah(value) : value}</CardContent></Card>)}</div><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Koperasi</TableHead><TableHead>PIC</TableHead><TableHead /></TableRow></TableHeader><TableBody>{needsAction.map((s: any) => <TableRow key={s.id}><TableCell>{s.submission_number}</TableCell><TableCell>{s.cooperative?.name}</TableCell><TableCell>{s.submitter?.name}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={s.status === 'approval_revision_requested' ? `/finance/approval-revisions/${s.id}` : `/finance/submissions/${s.id}`}>Buka</Link></Button></TableCell></TableRow>)}</TableBody></Table></div>;
}
