import { Badge } from '@/components/ui/badge';
const labels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Menunggu Finance',
    finance_review: 'Review Finance',
    revision_requested: 'Perlu Revisi',
    finance_validated: 'Tervalidasi',
    approval_review: 'Menunggu Approval',
    approval_in_review: 'Review Approval',
    director_review: 'Menunggu Director',
    director_in_review: 'Review Director',
    pending_disbursement: 'Menunggu Pembayaran',
    fund_disbursed: 'Dibayar',
    cancelled: 'Dibatalkan',
};
export function ReimbursementStatusBadge({ status }: { status: string }) {
    return (
        <Badge variant={status === 'fund_disbursed' ? 'default' : 'secondary'}>
            {labels[status] ?? status}
        </Badge>
    );
}
