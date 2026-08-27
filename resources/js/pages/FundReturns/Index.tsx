import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { FundReturnStatusBadge } from '@/components/FundReturns/FundReturnStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
export default function Index({
    returns,
    eligible = [],
    detailBasePath = '/fund-returns',
    exportUrl,
}: any) {
    return (
        <div className="space-y-5 p-4">
            <Head title="Pengembalian Sisa Dana" />
            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Pengembalian Sisa Dana
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Selesaikan sisa dana dari laporan pertanggungjawaban.
                    </p>
                </div>
                {exportUrl && (
                    <Button asChild variant="outline">
                        <a href={exportUrl}>
                            <Download />
                            Export Excel
                        </a>
                    </Button>
                )}
            </div>
            {eligible.map((r: any) => (
                <div
                    key={r.id}
                    className="flex flex-col justify-between gap-3 rounded-md border p-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <div className="font-medium">
                            {r.submission.submission_number} -{' '}
                            {r.submission.title}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Sisa yang harus dikembalikan:{' '}
                            {rupiah(r.remaining_amount)}
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={`/fund-returns/create/${r.id}`}>
                            Buat Pengembalian
                        </Link>
                    </Button>
                </div>
            ))}
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Pengajuan</TableHead>
                            <TableHead>Nominal</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {returns.data.map((r: any) => (
                            <TableRow key={r.id}>
                                <TableCell>
                                    <Link
                                        className="underline"
                                        href={`${detailBasePath}/${r.id}`}
                                    >
                                        {r.return_number}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    {r.submission?.submission_number}
                                </TableCell>
                                <TableCell>
                                    {rupiah(r.expected_amount)}
                                </TableCell>
                                <TableCell>
                                    <FundReturnStatusBadge status={r.status} />
                                </TableCell>
                                <TableCell>
                                    <Button variant="outline" size="sm">
                                        <Link
                                            className="w-full"
                                            href={`${detailBasePath}/${r.id}`}
                                        >
                                            Detail
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {!returns.data.length && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    Belum ada pengembalian dana.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
