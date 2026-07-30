import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

export default function GlobalMonitoring({ stats, byStatus }: any) {
    return <div className="space-y-4 p-4"><Head title="Monitoring Keuangan" /><h1 className="text-2xl font-semibold">Monitoring Keuangan</h1><div className="grid gap-3 md:grid-cols-4">{Object.entries(stats).map(([key, value]: any) => <Card key={key}><CardHeader><CardTitle>{key}</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{key.includes('amount') ? rupiah(value) : value}</CardContent></Card>)}</div><Table><TableHeader><TableRow><TableHead>Status</TableHead><TableHead>Jumlah</TableHead><TableHead>Nominal</TableHead></TableRow></TableHeader><TableBody>{byStatus.map((row: any) => <TableRow key={row.status}><TableCell>{row.status}</TableCell><TableCell>{row.aggregate}</TableCell><TableCell>{rupiah(row.amount)}</TableCell></TableRow>)}</TableBody></Table></div>;
}
