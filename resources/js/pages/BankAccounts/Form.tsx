import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function BankAccountForm({ account }: any) {
    const form = useForm({ bank_name: account?.bank_name ?? '', account_number: account?.account_number ?? '', account_holder_name: account?.account_holder_name ?? '', is_active: account?.is_active ?? true, is_primary: account?.is_primary ?? false });
    const submit = () => account ? form.put(`/bank-accounts/${account.id}`) : form.post('/bank-accounts');
    return <div className="max-w-xl space-y-4 p-4"><Head title="Rekening" /><h1 className="text-2xl font-semibold">{account ? 'Edit' : 'Tambah'} Rekening</h1>
        <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-4">
            <div><Label>Nama bank</Label><Input value={form.data.bank_name} onChange={(e) => form.setData('bank_name', e.target.value)} /></div>
            <div><Label>Nomor rekening</Label><Input value={form.data.account_number} onChange={(e) => form.setData('account_number', e.target.value)} /></div>
            <div><Label>Nama pada rekening</Label><Input value={form.data.account_holder_name} onChange={(e) => form.setData('account_holder_name', e.target.value)} /></div>
            <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.is_primary} onCheckedChange={(checked) => form.setData('is_primary', Boolean(checked))} /> Rekening utama</label>
            <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.is_active} onCheckedChange={(checked) => form.setData('is_active', Boolean(checked))} /> Aktif</label>
            <div className="flex gap-2"><Button>Simpan</Button><Button variant="outline" asChild><Link href="/bank-accounts">Batal</Link></Button></div>
        </form>
    </div>;
}
