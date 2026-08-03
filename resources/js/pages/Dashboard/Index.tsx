import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SharedData } from '@/types';
import { CreateSubmissionDialog } from '@/components/Submissions/CreateSubmissionDialog';

export default function DashboardIndex({
    cooperativesCount,
    submissionStats,
    fundStats = {},
}: {
    cooperativesCount: number;
    submissionStats: Record<string, number>;
    fundStats: Record<string, number>;
}) {
    const { auth } = usePage<SharedData>().props;
    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            {auth.user.name} ·{' '}
                            {auth.roles.join(', ') || 'tanpa role'}
                        </p>
                    </div>
                    {(auth.roles.includes('pic_kdkmp') ||
                        auth.roles.includes('finance_staff')) && (
                        <CreateSubmissionDialog triggerLabel="Tambah Pengajuan" />
                    )}
                </div>
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Koperasi dapat diakses</CardTitle>
                        </CardHeader>
                        <CardContent className="text-3xl font-semibold">
                            {cooperativesCount}
                        </CardContent>
                    </Card>
                    {Object.entries(submissionStats)
                        .slice(0, 7)
                        .map(([key, value]) => (
                            <Card key={key}>
                                <CardHeader>
                                    <CardTitle>{key}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-3xl font-semibold">
                                    {value}
                                </CardContent>
                            </Card>
                        ))}
                </div>
                {Object.keys(fundStats).length > 0 && (
                    <>
                        <h2 className="font-semibold">Perjalanan Dana</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {Object.entries(fundStats).map(([label, value]) => (
                                <Card key={label}>
                                    <CardHeader>
                                        <CardTitle className="text-sm">
                                            {label}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-2xl font-semibold">
                                        {label.includes('Dana')
                                            ? new Intl.NumberFormat('id-ID', {
                                                  style: 'currency',
                                                  currency: 'IDR',
                                                  maximumFractionDigits: 0,
                                              }).format(value)
                                            : value}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
