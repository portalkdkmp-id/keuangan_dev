import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SharedData } from '@/types';

export default function DashboardIndex({ cooperativesCount }: { cooperativesCount: number }) {
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
                    <Card><CardHeader><CardTitle>Pengajuan dana</CardTitle></CardHeader><CardContent className="text-muted-foreground">Phase 2</CardContent></Card>
                    <Card><CardHeader><CardTitle>Reimbursement</CardTitle></CardHeader><CardContent className="text-muted-foreground">Phase 2</CardContent></Card>
                    <Card><CardHeader><CardTitle>Approval</CardTitle></CardHeader><CardContent className="text-muted-foreground">Phase 2</CardContent></Card>
                </div>
            </div>
        </>
    );
}
