import { Head } from '@inertiajs/react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';

export default function AuditLogsIndex({ logs }: any) {
    return <div className="space-y-4 p-4"><Head title="Audit Logs" /><h1 className="text-2xl font-semibold">Audit Logs</h1>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Time</TableHead><TableHead>Event</TableHead><TableHead>User</TableHead><TableHead>Description</TableHead></TableRow></TableHeader><TableBody>{logs.data.map((log: any) => <TableRow key={log.id}><TableCell>{formatDate(log.created_at)}</TableCell><TableCell>{log.event}</TableCell><TableCell>{log.user?.name ?? '-'}</TableCell><TableCell>{log.description}</TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
