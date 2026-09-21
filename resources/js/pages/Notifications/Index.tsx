import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SimplePagination } from '@/components/simple-pagination';
import { formatDateTime } from '@/lib/format';

export default function NotificationsIndex({ notifications }: any) {
    return <div className="space-y-4 p-4"><Head title="Notifikasi" /><div className="flex items-center justify-between gap-3"><h1 className="text-2xl font-semibold">Notifikasi</h1><Button onClick={() => router.post('/notifications/read-all')}>Tandai semua dibaca</Button></div>
        <div className="space-y-2">{notifications.data.map((notification: any) => <button key={notification.id} onClick={() => router.post(`/notifications/${notification.id}/read`)} className={`block w-full rounded-md border p-3 text-left text-sm ${notification.read_at ? 'bg-muted/20' : 'border-primary/40 bg-primary/5'}`}>
            <div className="flex flex-wrap items-start justify-between gap-2"><strong>{notification.data.title ?? notification.data.submission_number ?? 'Notifikasi'}</strong><time className="text-xs text-muted-foreground">{formatDateTime(notification.created_at)}</time></div>
            {notification.data.submission_number && <div className="font-medium">{notification.data.submission_number}</div>}
            <div className="text-muted-foreground">{notification.data.message ?? notification.data.subject ?? notification.data.cooperative_name ?? ''}</div>
            {notification.data.rejection_reason && <div className="mt-1">Alasan: {notification.data.rejection_reason}</div>}
        </button>)}</div>
        <SimplePagination meta={notifications} />
    </div>;
}
