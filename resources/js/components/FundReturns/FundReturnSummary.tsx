import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { FundReturnStatusBadge } from './FundReturnStatusBadge';
export function FundReturnSummary({ fundReturn: r }: any) {
    return (
        <div className="space-y-4">
            <div className="flex justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {r.return_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {r.submission?.submission_number}
                    </p>
                </div>
                <FundReturnStatusBadge status={r.status} />
            </div>
            <div className="grid gap-3 border-y py-4 sm:grid-cols-3">
                <div>
                    <span className="text-xs text-muted-foreground">
                        Nominal
                    </span>
                    <div className="font-semibold">
                        {rupiah(r.expected_amount)}
                    </div>
                </div>
                <div>
                    <span className="text-xs text-muted-foreground">
                        Pengirim
                    </span>
                    <div>{r.returner?.name}</div>
                </div>
                <div>
                    <span className="text-xs text-muted-foreground">
                        Tujuan
                    </span>
                    <div>
                        {r.destination_bank_name_snapshot} -{' '}
                        {r.destination_account_number_snapshot}
                    </div>
                </div>
            </div>
            <div>
                <h2 className="font-medium">Bukti</h2>
                {r.attachments.map((a: any) => (
                    <a
                        key={a.id}
                        className="mr-3 text-sm underline"
                        href={`/fund-return-attachments/${a.id}/download`}
                    >
                        {a.original_name}
                    </a>
                ))}
            </div>
        </div>
    );
}
