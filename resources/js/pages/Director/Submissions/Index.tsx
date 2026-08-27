import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { SimplePagination } from '@/components/simple-pagination';
import { useState } from 'react';

const tabs = [
    ['director_review', 'Menunggu Review'],
    ['director_in_review', 'Sedang Direview'],
    ['director_revision_requested', 'Menunggu Revisi Approval'],
    ['pending_disbursement', 'Menunggu Pencairan'],
    ['fund_disbursed', 'Dana Terkirim'],
    ['director_rejected', 'Ditolak'],
];

export default function DirectorSubmissionsIndex({
    submissions,
    filters,
}: any) {
    const [startingId, setStartingId] = useState<string | null>(null);
    return (
        <div className="space-y-4 p-4">
            <Head title="Director Review" />
            <div>
                <h1 className="text-2xl font-semibold">Finance Director</h1>
                <p className="text-sm text-muted-foreground">
                    Daftar pengajuan yang masuk untuk review Director.
                </p>
            </div>
            <form
                className="flex gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        '/director/submissions',
                        {
                            ...filters,
                            ...Object.fromEntries(
                                new FormData(event.currentTarget),
                            ),
                        },
                        { preserveState: true },
                    );
                }}
            >
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Cari nomor, judul, item, atau koperasi"
                />
                <Button>Filter</Button>
            </form>
            <div className="flex flex-wrap gap-2 text-sm">
                <Button
                    size="sm"
                    variant={!filters.status ? 'default' : 'outline'}
                    onClick={() => router.get('/director/submissions')}
                >
                    Semua
                </Button>
                {tabs.map(([status, label]) => (
                    <Button
                        key={status}
                        size="sm"
                        variant={
                            filters.status === status ? 'default' : 'outline'
                        }
                        onClick={() =>
                            router.get(
                                '/director/submissions',
                                { status },
                                { preserveState: true },
                            )
                        }
                    >
                        {label}
                    </Button>
                ))}
            </div>
            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>PIC</TableHead>
                            <TableHead>Koperasi</TableHead>
                            <TableHead>Kategori</TableHead>
                            <TableHead>Approval</TableHead>
                            <TableHead>Director</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((s: any) => (
                            <TableRow key={s.id}>
                                <TableCell>{s.submission_number}</TableCell>
                                <TableCell>{s.title}</TableCell>
                                <TableCell>{s.submitter?.name}</TableCell>
                                <TableCell>{s.cooperative?.name}</TableCell>
                                <TableCell>
                                    {s.request_category?.name ?? '-'}
                                </TableCell>
                                <TableCell>
                                    {rupiah(s.approval_approved_amount)}
                                </TableCell>
                                <TableCell>
                                    {s.director_approved_amount
                                        ? rupiah(s.director_approved_amount)
                                        : '-'}
                                </TableCell>
                                <TableCell>
                                    <SubmissionStatusBadge status={s.status} />
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        {s.status === 'director_review' && (
                                            <Button
                                                size="sm"
                                                disabled={startingId === s.id}
                                                onClick={() => {
                                                    setStartingId(s.id);
                                                    router.post(
                                                        `/director/submissions/${s.id}/start-review`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                            onFinish: () =>
                                                                setStartingId(
                                                                    null,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                {startingId === s.id
                                                    ? 'Memulai...'
                                                    : 'Mulai Review'}
                                            </Button>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={`/director/submissions/${s.id}`}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                    </div>
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
