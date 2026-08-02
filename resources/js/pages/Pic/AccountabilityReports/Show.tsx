import { Head, Link, router } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { ReportDetail } from '@/components/Accountability/ReportDetail';
import { Button } from '@/components/ui/button';
export default function Show({ report }: any) {
    const editable = ['draft', 'revision_requested'].includes(report.status);
    return (
        <div className="space-y-4 p-4">
            <Head title={report.report_number} />
            <BackButton fallback="/accountability-reports" />
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {report.report_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {report.submission?.submission_number}
                    </p>
                </div>
                {editable && (
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/accountability-reports/${report.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button
                            onClick={() =>
                                router.post(
                                    `/accountability-reports/${report.id}/submit`,
                                )
                            }
                        >
                            Ajukan ke Finance
                        </Button>
                    </div>
                )}
            </div>
            <ReportDetail report={report} />
            {report.status === 'return_pending' && (
                <div className="flex flex-col gap-3 rounded-md border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="font-semibold">
                            Sisa Dana Harus Dikembalikan
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Buat pengembalian dan unggah bukti transfer.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={`/fund-returns/create/${report.id}`}>
                            Buat Pengembalian Dana
                        </Link>
                    </Button>
                </div>
            )}
            {report.status === 'reimbursement_pending' && (
                <div className="flex flex-col gap-3 rounded-md border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="font-semibold">Kekurangan Dana</h2>
                        <p className="text-sm text-muted-foreground">
                            Buat reimbursement untuk selisih realisasi.
                        </p>
                    </div>
                    <Button
                        onClick={() =>
                            router.post(
                                `/accountability-reports/${report.id}/create-reimbursement`,
                            )
                        }
                    >
                        Buat Reimbursement Selisih
                    </Button>
                </div>
            )}
        </div>
    );
}
