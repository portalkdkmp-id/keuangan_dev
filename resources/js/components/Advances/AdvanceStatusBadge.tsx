import { Badge } from '@/components/ui/badge';
const labels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Menunggu Review',
    under_review: 'Sedang Direview',
    approved: 'Proses Approval',
    pending_disbursement: 'Menunggu Pencairan',
    disbursed: 'Dicairkan',
    settlement_due: 'Settlement Belum Dibuat',
    settlement_draft: 'Settlement Draft',
    settlement_submitted: 'Settlement Diajukan',
    settlement_revision_requested: 'Perlu Revisi',
    settlement_verified: 'Settlement Terverifikasi',
    return_pending: 'Pengembalian Pending',
    reimbursement_pending: 'Reimbursement Pending',
    closed: 'Selesai',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
};
export function AdvanceStatusBadge({ status }: { status: string }) {
    return (
        <Badge variant={status === 'closed' ? 'default' : 'secondary'}>
            {labels[status] ?? status}
        </Badge>
    );
}
