import { Head, Link } from '@inertiajs/react';
import { ReportTable } from '@/components/Accountability/ReportTable';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
export default function Index({ reports, eligibleSubmissions }: any) {
    return (
        <div className="space-y-6 p-4">
            <Head title="Pertanggungjawaban Dana" />
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Laporan Penggunaan Dana
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Catat realisasi setelah penerimaan dana dikonfirmasi.
                    </p>
                </div>
                <Button asChild variant="outline">
                    <a href="/export-laporan/download/lpj">
                        <Download className="size-4" /> Export Penanggungjawaban
                    </a>
                </Button>
            </div>
            {eligibleSubmissions.length > 0 && (
                <div className="space-y-2">
                    <h2 className="font-semibold">Siap Dilaporkan</h2>
                    {eligibleSubmissions.map((item: any) => (
                        <div
                            key={item.id}
                            className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm"
                        >
                            <span>
                                {item.submission_number} · {item.title}
                            </span>
                            <Button size="sm" asChild>
                                <Link
                                    href={`/accountability-reports/create/${item.id}`}
                                >
                                    Buat Laporan
                                </Link>
                            </Button>
                        </div>
                    ))}
                </div>
            )}
            <ReportTable reports={reports} baseUrl="/accountability-reports" />
        </div>
    );
}
