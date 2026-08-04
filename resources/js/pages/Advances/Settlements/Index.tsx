import { Head, Link } from '@inertiajs/react';
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
        <div className="space-y-5 p-4 sm:p-6">
            <Head title="Settlement Uang Panjar" />
            <header>
                <h1 className="text-2xl font-semibold">
                    Settlement Uang Panjar
                </h1>
                <p className="text-sm text-muted-foreground">
                    Pertanggungjawaban panjar yang menjadi tanggung jawab Anda.
                </p>
            </header>
            <div className="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Pengajuan</TableHead>
                            <TableHead>Dana</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {advances.data.map((advance: any) => (
                            <TableRow key={advance.id}>
                                <TableCell>
                                    <div className="font-medium">
                                        {advance.submission.title}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {advance.submission.submission_number}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {rupiah(advance.disbursed_amount)}
                                </TableCell>
                                <TableCell>
                                    {advance.advance_status.replaceAll(
                                        '_',
                                        ' ',
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={
                                                advance.settlement
                                                    ? `/advance-settlements/${advance.settlement.id}`
                                                    : `/advance-settlements/create/${advance.id}`
                                            }
                                        >
                                            {advance.settlement
                                                ? 'Lihat'
                                                : 'Buat Settlement'}
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {advances.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    Belum ada panjar yang perlu diselesaikan.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
