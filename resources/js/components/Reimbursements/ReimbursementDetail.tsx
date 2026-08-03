import { rupiah } from '@/components/Submissions/SubmissionSummary';
export function ReimbursementDetail({ detail }: { detail: any }) {
    if (!detail) return null;
    return (
        <section className="space-y-3 rounded-md border p-4">
            <div className="flex justify-between gap-3">
                <div>
                    <h2 className="font-semibold">Detail Reimbursement</h2>
                    <p className="text-sm text-muted-foreground">
                        Rekening {detail.claimant_bank_name_snapshot} -{' '}
                        {detail.claimant_account_holder_snapshot}
                    </p>
                </div>
                <strong>{rupiah(detail.claimed_amount)}</strong>
            </div>
            {detail.expenses?.map((e: any) => (
                <div key={e.id} className="space-y-2 border-t pt-3">
                    <div className="flex justify-between gap-3 text-sm">
                        <div>
                            <div className="font-medium">{e.vendor_name}</div>
                            <div className="text-muted-foreground">
                                {e.description}
                            </div>
                        </div>
                        <strong>{rupiah(e.actual_amount)}</strong>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        {e.attachments?.map((a: any) => (
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
        </section>
    );
}
