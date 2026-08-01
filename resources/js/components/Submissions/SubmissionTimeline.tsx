import { CheckCircle2, CircleDot, Clock3 } from 'lucide-react';
import { SubmissionStatusBadge } from './SubmissionStatusBadge';
import { formatDate } from '@/lib/format';

function formatDateTime(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return `${formatDate(value)} ${new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))}`;
}

export function SubmissionTimeline({ histories }: { histories: any[] }) {
    if (!histories?.length) {
        return (
            <div className="rounded-md border p-4 text-sm text-muted-foreground">
                Belum ada aktivitas status.
            </div>
        );
    }

    return (
        <section className="rounded-md border p-4">
            <div className="mb-4 flex items-center gap-2">
                <Clock3 className="size-4 text-muted-foreground" />
                <h2 className="font-semibold">Riwayat Pengajuan</h2>
            </div>
            <ol className="relative space-y-5 border-s pl-5">
                {histories.map((history, index) => {
                    const isLatest = index === 0;

                    return (
                        <li key={history.id} className="relative text-sm">
                            <span className="absolute -left-[29px] flex size-4 items-center justify-center rounded-full bg-background">
                                {isLatest ? <CircleDot className="size-4 text-primary" /> : <CheckCircle2 className="size-4 text-muted-foreground" />}
                            </span>
                            <div className="space-y-2 rounded-md bg-muted/30 p-3">
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <SubmissionStatusBadge status={history.to_status} />
                                    <time className="text-xs text-muted-foreground">{formatDateTime(history.created_at)}</time>
                                </div>
                                <div className="font-medium">{history.action}</div>
                                <div className="text-muted-foreground">
                                    Oleh {history.actor?.name ?? '-'}
                                    {history.from_status ? <> dari <span className="font-medium text-foreground">{history.from_status}</span></> : null}
                                </div>
                                {history.notes ? <p className="whitespace-pre-line text-muted-foreground">{history.notes}</p> : null}
                            </div>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}
