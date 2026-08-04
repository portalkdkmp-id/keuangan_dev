import { Head, Link, router } from '@inertiajs/react';
import { AdvanceDeadlineBadge } from '@/components/Advances/AdvanceDeadlineBadge';
import { AdvanceStatusBadge } from '@/components/Advances/AdvanceStatusBadge';
import { BackButton } from '@/components/back-button';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
export default function Show({ submission }: any) {
    const d = submission.advance_detail;
    const editable = ['draft', 'revision_requested'].includes(
        submission.status,
    );
    return (
        <div className="mx-auto max-w-3xl space-y-5 p-4">
            <Head title={submission.submission_number} />
            <BackButton fallback="/submissions" />
            <div className="flex flex-wrap justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {submission.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {submission.submission_number}
                    </p>
                </div>
                <div className="flex gap-2">
                    <AdvanceStatusBadge status={d.advance_status} />
                    <AdvanceDeadlineBadge
                        date={d.expected_settlement_date}
                        closed={d.advance_status === 'closed'}
                    />
                </div>
            </div>
            <div className="grid gap-3 border-y py-4 sm:grid-cols-2">
                <div>
                    <span className="text-xs text-muted-foreground">
                        Estimasi
                    </span>
                    <div className="text-xl font-semibold">
                        {rupiah(d.estimated_amount)}
                    </div>
                </div>
                <div>
                    <span className="text-xs text-muted-foreground">
                        Penanggung jawab
                    </span>
                    <div>{d.responsible_user?.name}</div>
                </div>
                <div>
                    <span className="text-xs text-muted-foreground">
                        Tujuan
                    </span>
                    <div>{d.purpose}</div>
                </div>
                <div>
                    <span className="text-xs text-muted-foreground">
                        Rekening
                    </span>
                    <div>
                        {d.recipient_bank_name_snapshot} -{' '}
                        {d.recipient_account_holder_snapshot}
                    </div>
                </div>
            </div>
            <div className="flex flex-wrap gap-2">
                {editable && (
                    <Button variant="outline" asChild>
                        <Link href={`/advances/${submission.id}/edit`}>
                            Edit
                        </Link>
                    </Button>
                )}
                {editable && (
                    <Button
                        onClick={() =>
                            router.post(`/advances/${submission.id}/submit`)
                        }
                    >
                        Ajukan ke Keuangan
                    </Button>
                )}
                {['settlement_due', 'disbursed'].includes(d.advance_status) && (
                    <Button asChild>
                        <Link href={`/advance-settlements/create/${d.id}`}>
                            Buat Settlement
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
