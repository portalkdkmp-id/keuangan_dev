import { SubmissionStatusBadge } from './SubmissionStatusBadge';

export function SubmissionTimeline({ histories }: { histories: any[] }) {
    return (
        <div className="space-y-2">
            {histories.map((history) => (
                <div key={history.id} className="rounded-md border p-3 text-sm">
                    <SubmissionStatusBadge status={history.to_status} />
                    <div className="mt-1 text-muted-foreground">{history.action} · {history.actor?.name ?? '-'} · {history.created_at}</div>
                </div>
            ))}
        </div>
    );
}
