import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { ExportSubmissionsButton } from '@/components/Submissions/ExportSubmissionsButton';

export default function FinanceSubmissionsIndex({ submissions, filters }: any) {
    const sort = (column: string) =>
        router.get(
            '/finance/submissions',
            {
                ...filters,
                sort: column,
                direction:
                    filters.sort === column && filters.direction !== 'desc'
                        ? 'desc'
                        : 'asc',
            },
            { preserveState: true },
        );
    const setPerPage = (per_page: string) =>
        router.get(
            '/finance/submissions',
            { ...filters, per_page },
            { preserveState: true },
        );

    return (
        <div className="space-y-4 p-4">
            <Head title="Pengajuan Masuk" />
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 className="text-2xl font-semibold">Pengajuan Masuk</h1>
                <ExportSubmissionsButton />
            </div>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    router.get(
                        '/finance/submissions',
                        {
                            ...filters,
                            ...Object.fromEntries(
                                new FormData(e.currentTarget),
                            ),
                        },
                        { preserveState: true },
                    );
                }}
                className="flex gap-2"
            >
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Cari"
                />
                <Select
                    value={String(filters.per_page ?? 10)}
                    onValueChange={setPerPage}
                >
                    <SelectTrigger className="w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {[10, 25, 50, 100].map((value) => (
                            <SelectItem key={value} value={String(value)}>
                                {value}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button>Filter</Button>
            </form>
            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                <button
                                    onClick={() => sort('submission_number')}
                                >
                                    Nomor
                                </button>
                            </TableHead>
                            <TableHead>Koperasi</TableHead>
                            <TableHead>PIC</TableHead>
                            <TableHead>
                                <button onClick={() => sort('submitted_at')}>
                                    Tanggal
                                </button>
                            </TableHead>
                            <TableHead>
                                <button onClick={() => sort('amount')}>
                                    Total
                                </button>
                            </TableHead>
                            <TableHead>
                                <button onClick={() => sort('status')}>
                                    Status
                                </button>
                            </TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((s: any) => (
                            <TableRow key={s.id}>
                                <TableCell>{s.submission_number}</TableCell>
                                <TableCell>{s.cooperative?.name}</TableCell>
                                <TableCell>{s.submitter?.name}</TableCell>
                                <TableCell>
                                    {formatDate(s.created_at)}
                                </TableCell>
                                <TableCell>{rupiah(s.total_amount)}</TableCell>
                                <TableCell>
                                    <SubmissionStatusBadge status={s.status} />
                                </TableCell>
                                <TableCell className="space-x-2 text-right">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={`/finance/submissions/${s.id}`}
                                        >
                                            Detail
                                        </Link>
                                    </Button>
                                    {s.status === 'submitted' && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                router.post(
                                                    `/finance/submissions/${s.id}/start-review`,
                                                )
                                            }
                                        >
                                            Mulai Review
                                        </Button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
