import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SimplePagination } from '@/components/simple-pagination';
import { formatDate } from '@/lib/format';
import type { FormEvent } from 'react';

const statusLabels: Record<string, string> = {
    cancelled: 'Ditolak Staff / Dibatalkan',
    approval_rejected: 'Ditolak Approval',
    director_rejected: 'Ditolak Director',
    fund_disbursed: 'Selesai / Dicairkan',
};

export default function SubmissionHistoryIndex({ submissions, filters, statuses }: any) {
    const applyFilter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(event.currentTarget));
        if (values.status === 'all') values.status = '';
        router.get('/submission-history', values, { preserveState: true });
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="History Pengajuan" />
            <div>
                <h1 className="text-2xl font-semibold">History Pengajuan</h1>
                <p className="text-sm text-muted-foreground">Pengajuan yang sudah ditolak atau selesai tetap tersimpan di sini.</p>
            </div>
            <form onSubmit={applyFilter} className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_240px_auto]">
                <Input name="search" defaultValue={filters.search ?? ''} placeholder="Cari nomor, judul, item, koperasi" />
                <Select name="status" defaultValue={filters.status || 'all'}>
                    <SelectTrigger><SelectValue placeholder="Semua status" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua status</SelectItem>
                        {statuses.map((status: string) => <SelectItem key={status} value={status}>{statusLabels[status] ?? status}</SelectItem>)}
                    </SelectContent>
                </Select>
                <Button type="submit">Filter</Button>
            </form>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Judul</TableHead><TableHead>Pengaju</TableHead><TableHead>Koperasi</TableHead><TableHead>Dibuat</TableHead><TableHead>Total</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader>
                    <TableBody>
                        {submissions.data.length ? submissions.data.map((item: any) => (
                            <TableRow key={item.id}>
                                <TableCell>{item.submission_number}</TableCell>
                                <TableCell>{item.title}</TableCell>
                                <TableCell>{item.submitter?.name ?? '-'}</TableCell>
                                <TableCell>{item.cooperative?.name ?? 'Internal'}</TableCell>
                                <TableCell>{formatDate(item.created_at)}</TableCell>
                                <TableCell>{rupiah(item.total_amount)}</TableCell>
                                <TableCell><SubmissionStatusBadge status={item.status} /></TableCell>
                                <TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/submission-history/${item.id}`}>Lihat</Link></Button></TableCell>
                            </TableRow>
                        )) : <TableRow><TableCell colSpan={8} className="py-10 text-center text-muted-foreground">Belum ada history pengajuan.</TableCell></TableRow>}
                    </TableBody>
                </Table>
            </div>
            <SimplePagination meta={submissions} />
        </div>
    );
}
