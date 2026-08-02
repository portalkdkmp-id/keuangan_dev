import { Head, useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { BackButton } from '@/components/back-button';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export default function Create({ disbursement, remainingAmount }: any) {
    const submission = disbursement.submission;
    const form = useForm({
        idempotency_key: `${Date.now()}-${Math.random()}`,
        recipient_type: 'pic_kdkmp', recipient_user_id: submission.submitted_by, recipient_cooperative_id: '',
        destination_bank_account_id: '', recipient_name: '', destination_bank_name: '', destination_account_number: '', destination_account_holder: '',
        amount: remainingAmount, transfer_date: '', transferred_at: '', transaction_reference: '', payment_method: 'bank_transfer', notes: '', attachments: [] as File[],
    });
    const accounts = form.data.recipient_type === 'pic_kdkmp' ? (submission.submitter?.bank_accounts ?? []) : form.data.recipient_type === 'cooperative' ? (submission.cooperative?.bank_accounts ?? []) : [];
    const submit = () => {
        form.transform((data) => ({ ...data, transfer_date: data.transferred_at.slice(0, 10), transferred_at: data.transferred_at.replace('T', ' ') + (data.transferred_at.length === 16 ? ':00' : '') }));
        form.post(`/finance/fund-distributions/${disbursement.id}`, { forceFormData: true });
    };
    const changeRecipient = (value: string) => form.setData({ ...form.data, recipient_type: value, recipient_user_id: value === 'pic_kdkmp' ? submission.submitted_by : '', recipient_cooperative_id: value === 'cooperative' ? submission.cooperative_id : '', destination_bank_account_id: '' });

    return <div className="mx-auto max-w-3xl space-y-4 p-4"><Head title="Salurkan Dana" /><BackButton fallback="/finance/fund-distributions" />
        <div><h1 className="text-2xl font-semibold">Salurkan Dana</h1><p className="text-sm text-muted-foreground">{submission.submission_number} · Dana dari Director {rupiah(disbursement.amount)} · Sisa {rupiah(remainingAmount)}</p></div>
        <form className="space-y-4 rounded-md border p-4" onSubmit={(event) => { event.preventDefault(); submit(); }}>
            <Label>Jenis penerima</Label><Select value={form.data.recipient_type} onValueChange={changeRecipient}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="pic_kdkmp">PIC KDKMP</SelectItem><SelectItem value="cooperative">Koperasi</SelectItem><SelectItem value="other">Penerima Lain</SelectItem></SelectContent></Select>
            {form.data.recipient_type !== 'other' ? <><div className="rounded-md bg-muted p-3 text-sm">Penerima: {form.data.recipient_type === 'pic_kdkmp' ? submission.submitter?.name : submission.cooperative?.name}</div><Label>Rekening tujuan</Label><Select value={form.data.destination_bank_account_id} onValueChange={(value) => form.setData('destination_bank_account_id', value)}><SelectTrigger><SelectValue placeholder="Pilih rekening" /></SelectTrigger><SelectContent>{accounts.map((account: any) => <SelectItem key={account.id} value={account.id}>{account.bank_name} - {account.account_holder_name} - {account.account_number}</SelectItem>)}</SelectContent></Select></> : <><Label>Nama penerima</Label><Input value={form.data.recipient_name} onChange={(event) => form.setData('recipient_name', event.target.value)} /><Label>Bank tujuan</Label><Input value={form.data.destination_bank_name} onChange={(event) => form.setData('destination_bank_name', event.target.value)} /><Label>Nomor rekening</Label><Input value={form.data.destination_account_number} onChange={(event) => form.setData('destination_account_number', event.target.value)} /><Label>Nama pemilik rekening</Label><Input value={form.data.destination_account_holder} onChange={(event) => form.setData('destination_account_holder', event.target.value)} /></>}
            <Label>Nominal distribusi</Label><MoneyInput value={form.data.amount} onChange={(value) => form.setData('amount', value)} />
            <Label>Waktu transfer</Label><Input type="datetime-local" value={form.data.transferred_at} onChange={(event) => form.setData('transferred_at', event.target.value)} />
            <Label>Metode pembayaran</Label><Select value={form.data.payment_method} onValueChange={(value) => form.setData('payment_method', value)}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="bank_transfer">Bank Transfer</SelectItem><SelectItem value="cash">Cash</SelectItem><SelectItem value="virtual_account">Virtual Account</SelectItem><SelectItem value="other">Other</SelectItem></SelectContent></Select>
            <Label>Nomor referensi</Label><Input value={form.data.transaction_reference} onChange={(event) => form.setData('transaction_reference', event.target.value)} /><Label>Catatan</Label><Textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} /><Label>Bukti transfer</Label><Input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(event: ChangeEvent<HTMLInputElement>) => form.setData('attachments', Array.from(event.target.files ?? []))} /><Button disabled={form.processing}>Simpan Distribusi</Button>
        </form>
    </div>;
}
