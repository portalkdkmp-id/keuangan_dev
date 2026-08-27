import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';
import { CreateSubmissionDialog } from '@/components/Submissions/CreateSubmissionDialog';
import { ExportSubmissionsButton } from '@/components/Submissions/ExportSubmissionsButton';
import { SimplePagination } from '@/components/simple-pagination';

export default function SubmissionsIndex({ submissions, filters }: any) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Pengajuan Dana" />
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Pengajuan Dana</h1>
                    <p className="text-sm text-muted-foreground">
                        Pengajuan dana dan reimbursement Anda.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <ExportSubmissionsButton />
                    <CreateSubmissionDialog />
                </div>
            </div>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    router.get(
                        '/submissions',
                        Object.fromEntries(new FormData(e.currentTarget)),
                        { preserveState: true },
                    );
                }}
                className="flex gap-2"
            >
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Cari nomor, judul, item, atau koperasi"
                />
                <Button type="submit">Filter</Button>
            </form>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Jenis</TableHead>
                            <TableHead>Judul</TableHead>
                            <TableHead>Koperasi</TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((s: any) => (
                            <TableRow key={s.id}>
                                <TableCell>{s.submission_number}</TableCell>
                                <TableCell>
                                    {s.type === 'reimbursement'
                                        ? 'Reimbursement'
                                        : s.type === 'advance'
                                          ? 'Uang Panjar'
                                          : 'Pengajuan Dana'}
                                </TableCell>
                                <TableCell>{s.title}</TableCell>
                                <TableCell>{s.cooperative?.name}</TableCell>
                                <TableCell>
                                    {formatDate(s.created_at)}
                                </TableCell>
                                <TableCell>{rupiah(s.total_amount)}</TableCell>
                                <TableCell>
                                    <SubmissionStatusBadge status={s.status} />
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={
                                                s.type === 'reimbursement'
                                                    ? `/reimbursements/${s.id}`
                                                    : s.type === 'advance'
                                                      ? `/advances/${s.id}`
                                                      : `/submissions/${s.id}`
                                            }
                                        >
                                            Lihat
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            <SimplePagination meta={submissions} />
        </div>
    );
}
