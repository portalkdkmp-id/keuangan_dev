import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export default function DirectorDisbursementsShow({ disbursement }: any) {
    return <div className="space-y-4 p-4"><Head title={disbursement.disbursement_number} /><div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">{disbursement.disbursement_number}</h1><Button variant="outline" asChild><Link href={`/director/submissions/${disbursement.submission?.id}`}>Buka Pengajuan</Link></Button></div>
        <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2"><div>Pengajuan: {disbursement.submission?.submission_number}</div><div>Koperasi: {disbursement.submission?.cooperative?.name}</div><div>PIC: {disbursement.submission?.submitter?.name}</div><div>Nominal: {rupiah(disbursement.amount)}</div><div>Metode: {disbursement.payment_method}</div><div>Tanggal Transfer: {formatDate(disbursement.transferred_at)}</div><div>Bank sumber: {disbursement.bank_name ?? '-'}</div><div>Rekening sumber: {disbursement.source_account_number_masked ?? '-'}</div><div>Rekening tujuan: {disbursement.destination_bank_snapshot} - {disbursement.destination_account_holder_snapshot}</div><div>Nomor tujuan: {disbursement.destination_account_number_snapshot}</div><div>Referensi: {disbursement.transaction_reference ?? '-'}</div><div>Catatan: {disbursement.notes ?? '-'}</div></div>
        <div className="rounded-md border p-4 text-sm"><h2 className="font-semibold">Bukti Transfer</h2>{(disbursement.attachments ?? []).map((attachment: any) => <a key={attachment.id} className="mr-3 underline" href={`/director/disbursement-attachments/${attachment.id}/download`}>{attachment.original_name}</a>)}</div>
    </div>;
}
