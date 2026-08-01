import { Badge } from '@/components/ui/badge';

const labels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Menunggu Staff',
    finance_review: 'Direview Staff',
    revision_requested: 'Revisi',
    finance_validated: 'Tervalidasi Finance',
    approval_review: 'Menunggu Approval',
    approval_in_review: 'Direview Approval',
    approval_revision_requested: 'Revisi Finance',
    approval_rejected: 'Ditolak Approval',
    director_review: 'Menunggu Director',
    director_in_review: 'Direview Director',
    director_revision_requested: 'Revisi Approval',
    pending_disbursement: 'Menunggu Pencairan',
    fund_disbursed: 'Dana Terkirim',
    director_rejected: 'Ditolak Director',
    cancelled: 'Dibatalkan',
};

export function SubmissionStatusBadge({ status }: { status: string }) {
    return <Badge variant={status === 'draft' ? 'secondary' : 'default'}>{labels[status] ?? status}</Badge>;
}
