import { History } from 'lucide-react';
import { formatDateTime } from '@/lib/format';

function summary(value: unknown): string | null {
    if (!value) return null;
    if (typeof value === 'string') return value;
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'object') {
        const record = value as Record<string, unknown>;
        if (Array.isArray(record.fields)) return `Field diperbaiki: ${record.fields.join(', ')}`;

        return Object.entries(record).map(([key, item]) => `${key}: ${String(item)}`).join(', ');
    }

    return String(value);
}

export function SubmissionRevisionHistory({ submission }: { submission: any }) {
    const picRevisions = submission.revision_requests ?? [];
    const approvalRevisions = (submission.approval_reviews ?? []).filter(
        (review: any) => review.revision_message || review.change_summary,
    );
    const directorRevisions = (submission.director_reviews ?? []).filter(
        (review: any) => review.revision_message || review.change_summary,
    );
    const hasHistory = picRevisions.length || approvalRevisions.length || directorRevisions.length;

    return (
        <section className="space-y-3 border-t pt-4">
            <div className="flex items-center gap-2">
                <History className="size-4" />
                <h2 className="font-semibold">History Revisi</h2>
            </div>
            {!hasHistory ? (
                <p className="text-sm text-muted-foreground">Belum ada revisi pada pengajuan ini.</p>
            ) : (
                <div className="space-y-3">
                    {picRevisions.map((revision: any) => (
                        <div key={revision.id} className="border-l-2 pl-4 text-sm">
                            <div className="flex flex-wrap justify-between gap-2">
                                <strong>Revisi #{revision.revision_number}: {revision.subject}</strong>
                                <span className="text-muted-foreground">{formatDateTime(revision.requested_at)}</span>
                            </div>
                            <p className="mt-1 whitespace-pre-line">{revision.message}</p>
                            <p className="mt-1 text-muted-foreground">Diminta oleh {revision.requester?.name ?? '-'}</p>
                            {revision.response && (
                                <div className="mt-3 bg-muted/40 p-3">
                                    <strong>Perbaikan oleh {revision.response.responder?.name ?? 'pengaju'}</strong>
                                    <p className="whitespace-pre-line">{revision.response.message || 'Perbaikan telah dikirim ulang.'}</p>
                                    {summary(revision.response.change_summary) && <p className="text-muted-foreground">{summary(revision.response.change_summary)}</p>}
                                    <time className="text-xs text-muted-foreground">{formatDateTime(revision.response.responded_at)}</time>
                                </div>
                            )}
                        </div>
                    ))}
                    {approvalRevisions.map((review: any) => (
                        <div key={`approval-${review.id}`} className="border-l-2 border-amber-500 pl-4 text-sm">
                            <div className="flex flex-wrap justify-between gap-2">
                                <strong>Revisi Finance Approval #{review.review_number}</strong>
                                <span className="text-muted-foreground">{formatDateTime(review.decided_at ?? review.updated_at)}</span>
                            </div>
                            {review.revision_message && <p className="mt-1 whitespace-pre-line">{review.revision_message}</p>}
                            <p className="text-muted-foreground">Approval: {review.approver?.name ?? '-'}</p>
                            {summary(review.change_summary) && <div className="mt-2 bg-muted/40 p-3"><strong>Perbaikan yang dikirim:</strong><p className="whitespace-pre-line">{summary(review.change_summary)}</p></div>}
                        </div>
                    ))}
                    {directorRevisions.map((review: any) => (
                        <div key={`director-${review.id}`} className="border-l-2 border-blue-500 pl-4 text-sm">
                            <strong>Revisi Finance Director #{review.review_number}</strong>
                            {review.revision_message && <p className="mt-1 whitespace-pre-line">{review.revision_message}</p>}
                            <p className="text-muted-foreground">Director: {review.director?.name ?? '-'}</p>
                            {summary(review.change_summary) && <div className="mt-2 bg-muted/40 p-3"><strong>Perbaikan yang dikirim:</strong><p className="whitespace-pre-line">{summary(review.change_summary)}</p></div>}
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
