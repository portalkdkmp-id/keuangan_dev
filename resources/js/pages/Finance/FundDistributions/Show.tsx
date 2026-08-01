import { Head } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function Show({ distribution }: any) {
    return <div className="space-y-4 p-4"><Head title={distribution.distribution_number} /><BackButton fallback="/finance/fund-distributions" />
        <div><h1 className="text-2xl font-semibold">{distribution.distribution_number}</h1><p className="text-sm text-muted-foreground">{distribution.submission?.submission_number}</p></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2"><div>Penerima: {distribution.recipient_name_snapshot}</div><div>Nominal: {rupiah(distribution.amount)}</div><div>Rekening: {distribution.destination_bank_name_snapshot} - {distribution.destination_account_number_masked ?? '-'}</div><div>Transfer: {formatDate(distribution.transferred_at)}</div><div>Referensi: {distribution.transaction_reference ?? '-'}</div><div>Status: {distribution.status}</div><div className="md:col-span-2">Catatan: {distribution.notes ?? '-'}</div><div className="md:col-span-2">Bukti: {(distribution.attachments ?? []).map((attachment: any) => <a key={attachment.id} className="mr-3 underline" href={`/fund-distribution-attachments/${attachment.id}/download`}>{attachment.original_name}</a>)}</div></div>
    </div>;
}
