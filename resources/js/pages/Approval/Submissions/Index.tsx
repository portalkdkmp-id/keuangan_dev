import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
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
import { ExportSubmissionsButton } from '@/components/Submissions/ExportSubmissionsButton';

export default function ApprovalSubmissionsIndex({
    submissions,
    filters,
}: any) {
    const [startingReviewId, setStartingReviewId] = useState<string | null>(
        null,
    );

    return (
        <div className="space-y-4 p-4">
            <Head title="Approval Keuangan" />
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 className="text-2xl font-semibold">Approval Keuangan</h1>
                <ExportSubmissionsButton />
            </div>
            <div className="flex flex-wrap gap-2 text-sm">
                {[
                    'approval_review',
                    'approval_in_review',
                    'approval_revision_requested',
                    'director_review',
                    'approval_rejected',
                ].map((status) => (
                    <Button
                        key={status}
                        size="sm"
                        variant={
                            filters.status === status ? 'default' : 'outline'
                        }
                        onClick={() =>
                            router.get(
                                '/approval/submissions',
                                { status },
                                { preserveState: true },
                            )
                        }
                    >
                        {status}
                    </Button>
                ))}
            </div>
            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Koperasi</TableHead>
                            <TableHead>PIC</TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((submission: any) => (
                            <TableRow key={submission.id}>
                                <TableCell>
                                    {submission.submission_number}
                                </TableCell>
                                <TableCell>
                                    {submission.cooperative?.name}
                                </TableCell>
                                <TableCell>
                                    {submission.submitter?.name}
                                </TableCell>
                                <TableCell>
                                    {formatDate(submission.created_at)}
                                </TableCell>
                                <TableCell>
                                    {rupiah(submission.total_amount)}
                                </TableCell>
                                <TableCell>
                                    <SubmissionStatusBadge
                                        status={submission.status}
                                    />
                                </TableCell>
                                <TableCell className="space-x-2 text-right">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={`/approval/submissions/${submission.id}`}
                                        >
                                            Detail
                                        </Link>
                                    </Button>
                                    {submission.status ===
                                        'approval_review' && (
                                        <Button
                                            size="sm"
                                            disabled={
                                                startingReviewId ===
                                                submission.id
                                            }
                                            onClick={() => {
                                                setStartingReviewId(
                                                    submission.id,
                                                );
                                                router.post(
                                                    `/approval/submissions/${submission.id}/start-review`,
                                                    {},
                                                    {
                                                        onFinish: () =>
                                                            setStartingReviewId(
                                                                null,
                                                            ),
                                                    },
                                                );
                                            }}
                                        >
                                            {startingReviewId === submission.id
                                                ? 'Memulai...'
                                                : 'Mulai Review'}
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
