import { Badge } from '@/components/ui/badge';

const labels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Menunggu Staff',
    finance_review: 'Direview Staff',
    revision_requested: 'Revisi',
    cancelled: 'Dibatalkan',
};

export function SubmissionStatusBadge({ status }: { status: string }) {
    return <Badge variant={status === 'draft' ? 'secondary' : 'default'}>{labels[status] ?? status}</Badge>;
}
