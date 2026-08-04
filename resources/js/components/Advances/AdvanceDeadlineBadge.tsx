import { Badge } from '@/components/ui/badge';
export function AdvanceDeadlineBadge({
    date,
    closed = false,
}: {
    date: string;
    closed?: boolean;
}) {
    const due = new Date(date);
    const days = Math.ceil((due.getTime() - Date.now()) / 86400000);
    if (closed) return null;
    return days < 0 ? (
        <Badge variant="destructive">Terlambat {Math.abs(days)} hari</Badge>
    ) : (
        <Badge variant="outline">
            Deadline {new Intl.DateTimeFormat('id-ID').format(due)}
        </Badge>
    );
}
