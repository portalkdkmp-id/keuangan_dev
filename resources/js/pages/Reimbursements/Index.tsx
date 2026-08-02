import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ReimbursementStatusBadge } from '@/components/Reimbursements/ReimbursementStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
export default function Index({ submissions }: any) {
    return (
        <div className="space-y-5 p-4">
            <Head title="Reimbursement Saya" />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Reimbursement Saya
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Penggantian biaya yang telah Anda bayarkan.
                    </p>
                </div>
                <Button asChild>
                    <Link href="/reimbursements/create">
                        <Plus />
                        Buat
                    </Link>
                </Button>
            </div>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Judul</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((s: any) => (
                            <TableRow key={s.id}>
                                <TableCell>
                                    <Link
                                        className="font-medium underline"
                                        href={`/reimbursements/${s.id}`}
                                    >
                                        {s.submission_number}
                                    </Link>
                                </TableCell>
                                <TableCell>{s.title}</TableCell>
                                <TableCell>{rupiah(s.total_amount)}</TableCell>
                                <TableCell>
                                    <ReimbursementStatusBadge
                                        status={s.status}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                        {!submissions.data.length && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    Belum ada reimbursement.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
