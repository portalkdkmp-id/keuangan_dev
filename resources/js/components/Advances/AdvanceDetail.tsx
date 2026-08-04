import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export function AdvanceDetail({ detail }: { detail?: any }) {
    if (!detail) return null;

    return (
        <section className="space-y-3 border-y py-4">
            <h2 className="font-semibold">Detail Uang Panjar</h2>
            <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt className="text-muted-foreground">Penanggung jawab</dt>
                    <dd>{detail.responsible_user?.name ?? '-'}</dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Estimasi</dt>
                    <dd className="font-medium">
                        {rupiah(detail.estimated_amount)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Nominal disetujui</dt>
                    <dd>
                        {detail.approved_amount
                            ? rupiah(detail.approved_amount)
                            : '-'}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Estimasi transaksi
                    </dt>
                    <dd>{formatDate(detail.expected_transaction_date)}</dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Deadline settlement
                    </dt>
                    <dd>{formatDate(detail.expected_settlement_date)}</dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Status panjar</dt>
                    <dd>{detail.advance_status?.replaceAll('_', ' ')}</dd>
                </div>
                <div className="sm:col-span-2 lg:col-span-3">
                    <dt className="text-muted-foreground">Tujuan penggunaan</dt>
                    <dd>{detail.purpose}</dd>
                </div>
            </dl>
        </section>
    );
}
