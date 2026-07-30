import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

export default function ApprovalMonitoring({ stats }: any) {
    return <div className="space-y-4 p-4"><Head title="Dashboard Approval" /><h1 className="text-2xl font-semibold">Dashboard Approval</h1><div className="grid gap-3 md:grid-cols-4">{Object.entries(stats).map(([key, value]: any) => <Card key={key}><CardHeader><CardTitle>{key}</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{key.includes('amount') ? rupiah(value) : value}</CardContent></Card>)}</div></div>;
}
