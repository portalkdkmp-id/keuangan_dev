import { Head } from '@inertiajs/react';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function ApprovalSubmissionsShow({ submission }: any) {
    const detail = submission.finance_detail ?? submission.financeDetail;

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
            <div>Koperasi: {submission.cooperative?.name}</div>
            <div>Pengaju: {submission.submitter?.name}</div>
            <div>Area: {submission.submitter_city?.name ?? submission.submitterCity?.name ?? '-'}</div>
            <div>Kategori: {submission.request_category?.name ?? submission.requestCategory?.name ?? '-'}</div>
            <div>Jenis: {submission.request_type?.name ?? submission.requestType?.name ?? '-'}</div>
            <div>Rekening: {submission.recipient_bank_account ? `${submission.recipient_bank_account.bank_name} - ${submission.recipient_bank_account.account_holder_name}` : '-'}</div>
            <div>Tanggal diajukan: {formatDate(submission.created_at)}</div>
            <div>Tanggal dibutuhkan: {formatDate(submission.needed_date)}</div>
            <div className="font-semibold md:col-span-2">Nominal: {rupiah(submission.total_amount)}</div>
        </div>
        <p className="rounded-md border p-4 text-sm">{submission.purpose}</p>
        {detail && <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-3">
            <div><span className="text-muted-foreground">Metode bayar</span><div>{detail.payment_method}</div></div>
            <div><span className="text-muted-foreground">Penerima</span><div>{detail.beneficiary_name ?? '-'}</div></div>
            <div><span className="text-muted-foreground">Total validasi</span><div>{rupiah(detail.validated_total_amount)}</div></div>
            <div className="md:col-span-3"><span className="text-muted-foreground">Catatan finance</span><div>{detail.finance_notes ?? '-'}</div></div>
        </div>}
        <SubmissionAttachments submission={submission} />
        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
