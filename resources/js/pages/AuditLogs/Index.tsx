import { Head } from '@inertiajs/react';

export default function AuditLogsIndex({ logs }: any) {
    return <div className="space-y-4 p-4"><Head title="Audit Logs" /><h1 className="text-2xl font-semibold">Audit Logs</h1>
        <div className="overflow-hidden rounded-md border"><table className="w-full text-sm"><thead><tr className="border-b bg-muted"><th className="p-3 text-left">Time</th><th>Event</th><th>User</th><th>Description</th></tr></thead><tbody>{logs.data.map((log: any) => <tr key={log.id} className="border-b"><td className="p-3">{log.created_at}</td><td>{log.event}</td><td>{log.user?.name ?? '-'}</td><td>{log.description}</td></tr>)}</tbody></table></div>
    </div>;
}
