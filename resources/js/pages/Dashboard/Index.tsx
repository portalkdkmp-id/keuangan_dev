import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SharedData } from '@/types';

export default function DashboardIndex({ cooperativesCount, submissionStats }: { cooperativesCount: number; submissionStats: Record<string, number> }) {
    const { auth } = usePage<SharedData>().props;
    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Dashboard</h1>
                    <p className="text-sm text-muted-foreground">{auth.user.name} · {auth.roles.join(', ') || 'tanpa role'}</p>
                </div>
                <div className="grid gap-4 md:grid-cols-4">
                    <Card><CardHeader><CardTitle>Koperasi dapat diakses</CardTitle></CardHeader><CardContent className="text-3xl font-semibold">{cooperativesCount}</CardContent></Card>
                    {Object.entries(submissionStats).slice(0, 7).map(([key, value]) => <Card key={key}><CardHeader><CardTitle>{key}</CardTitle></CardHeader><CardContent className="text-3xl font-semibold">{value}</CardContent></Card>)}
                </div>
            </div>
        </>
    );
}
