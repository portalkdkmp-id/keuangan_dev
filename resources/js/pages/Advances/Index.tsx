import { Head, Link } from '@inertiajs/react';
import { AdvanceStatusBadge } from '@/components/Advances/AdvanceStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
export default function Index({ advances }: any) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Uang Panjar" />
            <h1 className="text-2xl font-semibold">Uang Panjar</h1>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Judul</TableHead>
                            <TableHead>Estimasi</TableHead>
                            <TableHead>Deadline</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {advances.data.map((s: any) => (
                            <TableRow key={s.id}>
                                <TableCell>{s.submission_number}</TableCell>
                                <TableCell>{s.title}</TableCell>
                                <TableCell>
                                    {rupiah(s.advance_detail?.estimated_amount)}
                                </TableCell>
                                <TableCell>
                                    {s.advance_detail?.expected_settlement_date}
                                </TableCell>
                                <TableCell>
                                    <AdvanceStatusBadge
                                        status={
                                            s.advance_detail?.advance_status
                                        }
                                    />
                                </TableCell>
                                <TableCell>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href={`/advances/${s.id}`}>
                                            Lihat
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
