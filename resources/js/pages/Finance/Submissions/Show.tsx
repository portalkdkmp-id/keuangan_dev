import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { formatDate } from '@/lib/format';

export default function FinanceSubmissionsShow({ submission }: any) {
    const detail = submission.finance_detail ?? submission.financeDetail ?? {};
    const financeForm = useForm({
        budget_account_code: detail.budget_account_code ?? '',
        budget_account_name: detail.budget_account_name ?? '',
        cost_center_code: detail.cost_center_code ?? '',
        cost_center_name: detail.cost_center_name ?? '',
        expense_group: detail.expense_group ?? '',
        payment_method: detail.payment_method ?? 'bank_transfer',
        beneficiary_name: detail.beneficiary_name ?? '',
        beneficiary_bank: detail.beneficiary_bank ?? '',
        beneficiary_account_number: detail.beneficiary_account_number ?? '',
        beneficiary_account_holder: detail.beneficiary_account_holder ?? '',
        tax_applicable: Boolean(detail.tax_applicable),
        tax_notes: detail.tax_notes ?? '',
        finance_notes: detail.finance_notes ?? '',
        validated_total_amount: detail.validated_total_amount ?? submission.total_amount,
    });
    const revisionForm = useForm({ subject: '', message: '', fields: [] as string[] });
    const isReview = submission.status === 'finance_review';
    const isValidated = submission.status === 'finance_validated';
    const revisionFields = ['cooperative', 'title', 'purpose', 'needed_date', 'items', 'attachments', 'other'];

    const toggleField = (field: string, checked: boolean) => {
        revisionForm.setData('fields', checked ? [...revisionForm.data.fields, field] : revisionForm.data.fields.filter((value) => value !== field));
    };

    return <div className="space-y-4 p-4"><Head title={submission.submission_number} />
        <div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><SubmissionStatusBadge status={submission.status} /></div>
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
        <SubmissionAttachments submission={submission} />

        {submission.status === 'submitted' && <Button onClick={() => router.post(`/finance/submissions/${submission.id}/start-review`)}>Mulai Review</Button>}

        {(isReview || isValidated || submission.status === 'approval_review') && <form onSubmit={(e) => { e.preventDefault(); financeForm.put(`/finance/submissions/${submission.id}/finance-detail`); }} className="space-y-4 rounded-md border p-4">
            <h2 className="font-semibold">Detail Validasi Finance</h2>
            <div className="grid gap-3 md:grid-cols-3">
                <div><Label>Kode akun anggaran</Label><Input value={financeForm.data.budget_account_code} onChange={(e) => financeForm.setData('budget_account_code', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Nama akun anggaran</Label><Input value={financeForm.data.budget_account_name} onChange={(e) => financeForm.setData('budget_account_name', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Kode cost center</Label><Input value={financeForm.data.cost_center_code} onChange={(e) => financeForm.setData('cost_center_code', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Nama cost center</Label><Input value={financeForm.data.cost_center_name} onChange={(e) => financeForm.setData('cost_center_name', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Kelompok biaya</Label><Input value={financeForm.data.expense_group} onChange={(e) => financeForm.setData('expense_group', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Metode bayar</Label><Select value={financeForm.data.payment_method} onValueChange={(value) => financeForm.setData('payment_method', value)} disabled={!isReview}><SelectTrigger className="w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="bank_transfer">Transfer Bank</SelectItem><SelectItem value="cash">Tunai</SelectItem><SelectItem value="virtual_account">Virtual Account</SelectItem><SelectItem value="other">Lainnya</SelectItem></SelectContent></Select></div>
                <div><Label>Bank penerima</Label><Input value={financeForm.data.beneficiary_bank} onChange={(e) => financeForm.setData('beneficiary_bank', e.target.value)} disabled={!isReview} /></div>
                <div><Label>No. rekening</Label><Input value={financeForm.data.beneficiary_account_number} onChange={(e) => financeForm.setData('beneficiary_account_number', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Nama rekening</Label><Input value={financeForm.data.beneficiary_account_holder} onChange={(e) => financeForm.setData('beneficiary_account_holder', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Penerima</Label><Input value={financeForm.data.beneficiary_name} onChange={(e) => financeForm.setData('beneficiary_name', e.target.value)} disabled={!isReview} /></div>
                <div><Label>Total tervalidasi</Label><Input type="number" value={financeForm.data.validated_total_amount} onChange={(e) => financeForm.setData('validated_total_amount', e.target.value)} disabled={!isReview} /></div>
                <label className="flex items-center gap-2 pt-6 text-sm"><Checkbox checked={financeForm.data.tax_applicable} onCheckedChange={(checked) => financeForm.setData('tax_applicable', Boolean(checked))} disabled={!isReview} /> Kena pajak</label>
            </div>
            <div><Label>Catatan pajak</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={financeForm.data.tax_notes} onChange={(e) => financeForm.setData('tax_notes', e.target.value)} disabled={!isReview} /></div>
            <div><Label>Catatan finance</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={financeForm.data.finance_notes} onChange={(e) => financeForm.setData('finance_notes', e.target.value)} disabled={!isReview} /></div>
            {isReview && <div className="flex gap-2"><Button type="submit" disabled={financeForm.processing}>Simpan Detail</Button><Button type="button" variant="outline" onClick={() => router.post(`/finance/submissions/${submission.id}/validate`)}>Validasi</Button></div>}
            {isValidated && <Button type="button" onClick={() => router.post(`/finance/submissions/${submission.id}/forward-approval`)}>Teruskan ke Approval</Button>}
        </form>}

        {isReview && <form onSubmit={(e) => { e.preventDefault(); revisionForm.post(`/finance/submissions/${submission.id}/request-revision`); }} className="space-y-3 rounded-md border p-4">
            <h2 className="font-semibold">Minta Revisi ke PIC</h2>
            <Input placeholder="Subjek revisi" value={revisionForm.data.subject} onChange={(e) => revisionForm.setData('subject', e.target.value)} />
            <textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" placeholder="Pesan revisi" value={revisionForm.data.message} onChange={(e) => revisionForm.setData('message', e.target.value)} />
            <div className="flex flex-wrap gap-3 text-sm">{revisionFields.map((field) => <label key={field} className="flex items-center gap-2"><Checkbox checked={revisionForm.data.fields.includes(field)} onCheckedChange={(checked) => toggleField(field, Boolean(checked))} />{field}</label>)}</div>
            <Button variant="outline" disabled={revisionForm.processing}>Kirim Revisi</Button>
        </form>}

        <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} />
    </div>;
}
