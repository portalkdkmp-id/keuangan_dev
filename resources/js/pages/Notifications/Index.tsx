import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function NotificationsIndex({ notifications }: any) {
    return <div className="space-y-4 p-4"><Head title="Notifications" /><div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">Notifications</h1><Button onClick={() => router.post('/notifications/read-all')}>Tandai semua dibaca</Button></div>
        <div className="space-y-2">{notifications.data.map((notification: any) => <button key={notification.id} onClick={() => router.post(`/notifications/${notification.id}/read`)} className="block w-full rounded-md border p-3 text-left text-sm"><strong>{notification.data.submission_number ?? 'Notification'}</strong><div>{notification.data.cooperative_name}</div></button>)}</div>
    </div>;
}
