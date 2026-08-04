import { Head, Link, router } from '@inertiajs/react';
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

export default function Show({ report }: any) {
    const editable = ['draft', 'revision_requested'].includes(report.status);
    return (
        <div className="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
            <Head title={report.report_number} />
            <header>
                <p className="text-sm text-muted-foreground">
                    {report.report_number}
                </p>
                <h1 className="text-2xl font-semibold">
                    {report.advance_detail.submission.title}
                </h1>
                <p className="text-sm">
                    Status: {report.status.replaceAll('_', ' ')}
                </p>
            </header>
            <dl className="grid gap-4 border-y py-4 sm:grid-cols-4">
                <div>
                    <dt className="text-sm text-muted-foreground">
                        Dana panjar
                    </dt>
                    <dd className="font-semibold">
                        {rupiah(report.received_amount)}
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-muted-foreground">Realisasi</dt>
                    <dd className="font-semibold">
                        {rupiah(report.realized_amount)}
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-muted-foreground">Sisa</dt>
                    <dd>{rupiah(report.remaining_amount)}</dd>
                </div>
                <div>
                    <dt className="text-sm text-muted-foreground">
                        Kekurangan
                    </dt>
                    <dd>{rupiah(report.additional_amount)}</dd>
                </div>
            </dl>
            <div className="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Transaksi</TableHead>
                            <TableHead>Vendor</TableHead>
                            <TableHead>Nominal</TableHead>
                            <TableHead>Bukti</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {report.items.map((item: any) => (
                            <TableRow key={item.id}>
                                <TableCell>
                                    {new Date(
                                        item.expense_date,
                                    ).toLocaleDateString('id-ID')}
                                </TableCell>
                                <TableCell>{item.description}</TableCell>
                                <TableCell>{item.vendor_name}</TableCell>
                                <TableCell>{rupiah(item.amount)}</TableCell>
                                <TableCell>
                                    {item.attachments.length} file
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            <div className="flex flex-wrap gap-2">
                {editable && (
                    <>
                        <Button asChild variant="outline">
                            <Link
                                href={`/advance-settlements/${report.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button
                            onClick={() =>
                                router.post(
                                    `/advance-settlements/${report.id}/submit`,
                                )
                            }
                        >
                            Ajukan ke Keuangan
                        </Button>
                    </>
                )}
                {report.status === 'return_pending' && !report.fund_return && (
                    <Button asChild>
                        <Link href={`/fund-returns/create/${report.id}`}>
                            Kembalikan Sisa Dana
                        </Link>
                    </Button>
                )}
                {report.status === 'reimbursement_pending' &&
                    !report.generated_reimbursement && (
                        <Button
                            onClick={() =>
                                router.post(
                                    `/accountability-reports/${report.id}/create-reimbursement`,
                                )
                            }
                        >
                            Buat Reimbursement Selisih
                        </Button>
                    )}
                {report.fund_return && (
                    <Button asChild variant="outline">
                        <Link href={`/fund-returns/${report.fund_return.id}`}>
                            Lihat Pengembalian
                        </Link>
                    </Button>
                )}
                {report.generated_reimbursement && (
                    <Button asChild variant="outline">
                        <Link
                            href={`/reimbursements/${report.generated_reimbursement.financial_submission_id}`}
                        >
                            Lihat Reimbursement
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
