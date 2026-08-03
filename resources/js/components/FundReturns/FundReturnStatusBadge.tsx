import { Badge } from '@/components/ui/badge';
const labels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Menunggu Finance',
    finance_review: 'Review Finance',
    revision_requested: 'Perlu Revisi',
    finance_verified: 'Menunggu Approval',
    rejected: 'Ditolak',
    closed: 'Selesai',
};
export function FundReturnStatusBadge({ status }: { status: string }) {
    return (
        <Badge variant={status === 'closed' ? 'default' : 'secondary'}>
            {labels[status] ?? status}
        </Badge>
    );
}
