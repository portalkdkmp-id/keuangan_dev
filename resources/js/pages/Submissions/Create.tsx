import { Head, router, useForm } from '@inertiajs/react';
import { CalendarIcon, FileText } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

function localDate(value?: string | null): Date | undefined {
    if (!value) return undefined;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function dateValue(date?: Date): string {
    if (!date) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export default function SubmissionsCreate({ cooperatives, requestCategories, requestTypes, bankAccounts }: any) {
    const [step, setStep] = useState(1);
    const [accountOpen, setAccountOpen] = useState(false);
    const [dateOpen, setDateOpen] = useState(false);
    const form = useForm({
        title: '',
        cooperative_id: cooperatives[0]?.id ?? '',
        submission_request_category_id: '',
        submission_request_type_id: '',
        recipient_bank_account_id: bankAccounts[0]?.id ?? '',
        amount: '',
        needed_date: '',
        notes: '',
        attachments: [] as File[],
        action: 'draft',
    });
    const accountForm = useForm({ bank_name: '', account_number: '', account_holder_name: '', is_active: true, is_primary: bankAccounts.length === 0 });
    const selectedCategory = requestCategories.find((category: any) => category.id === form.data.submission_request_category_id);
    const selectedType = requestTypes.find((type: any) => type.id === form.data.submission_request_type_id);
    const selectedCooperative = cooperatives.find((cooperative: any) => cooperative.id === form.data.cooperative_id);
    const selectedAccount = bankAccounts.find((account: any) => account.id === form.data.recipient_bank_account_id);
    const amount = useMemo(() => Number(form.data.amount || 0), [form.data.amount]);
    const errors = Object.values(form.errors ?? {}) as string[];
    const canStep1 = Boolean(form.data.submission_request_category_id) && Boolean(form.data.title) && Boolean(form.data.cooperative_id) && Boolean(form.data.submission_request_type_id);
    const canStep2 = Boolean(form.data.amount) && Boolean(form.data.needed_date) && Boolean(form.data.recipient_bank_account_id);

    const submit = (action: 'draft' | 'submit') => {
        form.setData('action', action);
        form.transform((data) => ({ ...data, action }));
        form.post('/submissions', { forceFormData: true });
    };

    return <div className="space-y-4 p-4"><Head title="Buat Pengajuan" />
        <div><h1 className="text-2xl font-semibold">Buat Pengajuan Dana</h1><p className="text-sm text-muted-foreground">Langkah {step} dari 3</p></div>
        {errors.length > 0 && <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">{errors[0]}</div>}

        {step === 1 && <div className="space-y-4 rounded-md border p-4">
            <h2 className="font-semibold">Data Pengajuan</h2>
            <div className="grid gap-4 md:grid-cols-2">
                <div><Label>Kategori Pengajuan</Label><Select value={form.data.submission_request_category_id || undefined} onValueChange={(value) => form.setData('submission_request_category_id', value)}><SelectTrigger className="w-full"><SelectValue placeholder="Pilih kategori" /></SelectTrigger><SelectContent>{requestCategories.map((category: any) => <SelectItem key={category.id} value={category.id}>{category.name}</SelectItem>)}</SelectContent></Select></div>
                <div><Label>Title Pengajuan</Label><Input value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder="Contoh: Pengajuan ATK bulan Juli" /></div>
                <div><Label>Koperasi</Label><Select value={form.data.cooperative_id || undefined} onValueChange={(value) => form.setData('cooperative_id', value)}><SelectTrigger className="w-full"><SelectValue placeholder="Pilih koperasi" /></SelectTrigger><SelectContent>{cooperatives.map((cooperative: any) => <SelectItem key={cooperative.id} value={cooperative.id}>{cooperative.name}</SelectItem>)}</SelectContent></Select></div>
                <div><Label>Jenis Pengajuan</Label><Select value={form.data.submission_request_type_id || undefined} onValueChange={(value) => form.setData('submission_request_type_id', value)}><SelectTrigger className="w-full"><SelectValue placeholder="Pilih jenis" /></SelectTrigger><SelectContent>{requestTypes.map((type: any) => <SelectItem key={type.id} value={type.id}>{type.name}</SelectItem>)}</SelectContent></Select></div>
            </div>
            <div className="flex justify-end"><Button disabled={!canStep1} onClick={() => setStep(2)}>Lanjut</Button></div>
        </div>}

        {step === 2 && <div className="space-y-4 rounded-md border p-4">
            <h2 className="font-semibold">Nominal, Rekening, dan Bukti</h2>
            <div className="grid gap-4 md:grid-cols-2">
                <div><Label>Nominal</Label><Input inputMode="numeric" value={form.data.amount ? rupiah(amount) : ''} onChange={(event) => form.setData('amount', event.target.value.replace(/\D/g, ''))} placeholder="Rp 0" /></div>
                <div><Label>Tanggal Dibutuhkan</Label><Popover open={dateOpen} onOpenChange={setDateOpen}><PopoverTrigger asChild><Button variant="outline" className="w-full justify-start text-left font-normal"><CalendarIcon />{form.data.needed_date ? formatDate(form.data.needed_date) : 'Pilih tanggal'}</Button></PopoverTrigger><PopoverContent className="w-auto p-0"><Calendar mode="single" selected={localDate(form.data.needed_date)} onSelect={(date) => { form.setData('needed_date', dateValue(date)); setDateOpen(false); }} disabled={(date) => date < new Date(new Date().setHours(0, 0, 0, 0))} /></PopoverContent></Popover></div>
                <div className="md:col-span-2"><div className="mb-1 flex items-center justify-between"><Label>Rekening Penerima</Label><Button type="button" size="sm" variant="outline" onClick={() => setAccountOpen(true)}>Tambah Rekening</Button></div><Select value={form.data.recipient_bank_account_id || undefined} onValueChange={(value) => form.setData('recipient_bank_account_id', value)}><SelectTrigger className="w-full"><SelectValue placeholder="Pilih rekening" /></SelectTrigger><SelectContent>{bankAccounts.map((account: any) => <SelectItem key={account.id} value={account.id}>{account.bank_name} - {account.account_holder_name} - {account.account_number}</SelectItem>)}</SelectContent></Select></div>
                <div className="md:col-span-2"><Label>Catatan</Label><Textarea className="min-h-28" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} /></div>
                <div className="md:col-span-2"><Label>Attachment</Label><Input type="file" multiple onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))} /></div>
            </div>
            <div className="flex justify-between gap-2"><Button variant="outline" onClick={() => setStep(1)}>Kembali</Button><Button disabled={!canStep2} onClick={() => setStep(3)}>Review Pengajuan</Button></div>
        </div>}

        {step === 3 && <div className="space-y-4 rounded-md border p-4">
            <h2 className="font-semibold">Review Pengajuan</h2>
            <div className="grid gap-3 text-sm md:grid-cols-2"><div>Kategori: {selectedCategory?.name ?? '-'}</div><div>Title: {form.data.title}</div><div>Koperasi: {selectedCooperative?.name ?? '-'}</div><div>Jenis: {selectedType?.name ?? '-'}</div><div>Nominal: {rupiah(amount)}</div><div>Tanggal Dibutuhkan: {formatDate(form.data.needed_date)}</div><div className="md:col-span-2">Rekening Penerima: {selectedAccount ? `${selectedAccount.bank_name} - ${selectedAccount.account_holder_name} - ${selectedAccount.account_number}` : '-'}</div><div className="md:col-span-2">Catatan: {form.data.notes || '-'}</div></div>
            <div className="space-y-2"><h3 className="text-sm font-medium">Attachment</h3>{form.data.attachments.length === 0 && <p className="text-sm text-muted-foreground">Belum ada attachment.</p>}<div className="grid gap-2 md:grid-cols-2">{form.data.attachments.map((file) => <AttachmentPreview key={`${file.name}-${file.size}`} file={file} />)}</div></div>
            <div className="flex flex-col gap-2 sm:flex-row sm:justify-end"><Button variant="outline" onClick={() => setStep(2)}>Edit Pengajuan</Button><Button variant="outline" onClick={() => submit('draft')} disabled={form.processing}>Simpan Draft</Button><Button onClick={() => submit('submit')} disabled={form.processing}>Ajukan ke Keuangan</Button><Button variant="outline" onClick={() => router.visit('/submissions')}>Batal</Button></div>
        </div>}

        <Dialog open={accountOpen} onOpenChange={setAccountOpen}><DialogContent><DialogHeader><DialogTitle>Tambah Rekening</DialogTitle></DialogHeader><div className="space-y-3"><div><Label>Nama Bank</Label><Input value={accountForm.data.bank_name} onChange={(event) => accountForm.setData('bank_name', event.target.value)} /></div><div><Label>Nomor Rekening</Label><Input value={accountForm.data.account_number} onChange={(event) => accountForm.setData('account_number', event.target.value)} /></div><div><Label>Nama Pemilik Rekening</Label><Input value={accountForm.data.account_holder_name} onChange={(event) => accountForm.setData('account_holder_name', event.target.value)} /></div></div><DialogFooter><DialogClose asChild><Button variant="outline">Batal</Button></DialogClose><Button disabled={accountForm.processing} onClick={() => accountForm.post('/bank-accounts/quick-store', { onSuccess: () => { setAccountOpen(false); router.reload({ only: ['bankAccounts'] }); } })}>Simpan</Button></DialogFooter></DialogContent></Dialog>
    </div>;
}

function AttachmentPreview({ file }: { file: File }) {
    const isImage = file.type.startsWith('image/');
    const previewUrl = useMemo(() => isImage ? URL.createObjectURL(file) : null, [file, isImage]);

    useEffect(() => () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    }, [previewUrl]);

    return <div className="rounded-md border p-3 text-sm">
        {previewUrl ? <img src={previewUrl} alt={file.name} className="mb-2 max-h-64 w-full rounded-md object-contain" /> : <div className="mb-2 flex h-28 items-center justify-center rounded-md bg-muted"><FileText className="size-8" /></div>}
        <div className="break-all">{file.name}</div>
    </div>;
}
