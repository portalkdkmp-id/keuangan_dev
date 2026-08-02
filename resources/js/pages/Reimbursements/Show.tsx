import { Head, Link, router } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { ReimbursementStatusBadge } from '@/components/Reimbursements/ReimbursementStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
export default function Show({ submission }: any) {
    const d = submission.reimbursement_detail;
    const editable = ['draft', 'revision_requested'].includes(
        submission.status,
    );
    return (
        <div className="mx-auto max-w-4xl space-y-5 p-4">
            <Head title={submission.submission_number} />
            <BackButton fallback="/reimbursements" />
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {submission.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {submission.submission_number}
                    </p>
                </div>
                <ReimbursementStatusBadge status={submission.status} />
            </div>
            <div className="grid gap-3 border-y py-4 sm:grid-cols-3">
                <div>
                    <div className="text-xs text-muted-foreground">
                        Claimant
                    </div>
                    {submission.submitter.name}
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">
                        Rekening
                    </div>
                    {d.claimant_bank_name_snapshot} -{' '}
                    {d.claimant_account_number_snapshot}
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">Total</div>
                    <strong>{rupiah(d.claimed_amount)}</strong>
                </div>
            </div>
            {d.expenses.map((e: any) => (
                <div key={e.id} className="space-y-3 rounded-md border p-4">
                    <div className="flex justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">{e.vendor_name}</h2>
                            <p className="text-sm text-muted-foreground">
                                {e.description}
                            </p>
                        </div>
                        <strong>{rupiah(e.actual_amount)}</strong>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {e.attachments.map((a: any) => (
                            <a
                                key={a.id}
                                className="text-sm underline"
                                href={`/reimbursement-attachments/${a.id}/download`}
                            >
                                {a.attachment_type === 'purchase_proof'
                                    ? 'Bukti pembelian'
                                    : 'Bukti pembayaran'}
                                : {a.original_name}
                            </a>
                        ))}
                    </div>
                </div>
            ))}
            {editable && (
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={`/reimbursements/${submission.id}/edit`}>
                            Edit
                        </Link>
                    </Button>
                    <Button
                        onClick={() =>
                            router.post(
                                `/reimbursements/${submission.id}/submit`,
                            )
                        }
                    >
                        Ajukan ke Keuangan
                    </Button>
                </div>
            )}
        </div>
    );
}
