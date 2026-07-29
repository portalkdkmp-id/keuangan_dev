import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SubmissionSummary } from './SubmissionSummary';

export function SubmissionForm({ form, cooperatives, categories, onSubmit, submitLabel = 'Simpan Draft' }: any) {
    const items = form.data.items;
    const total = useMemo(() => items.reduce((sum: number, item: any) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0), [items]);
    const updateItem = (index: number, key: string, value: any) => {
        const next = [...items];
        next[index] = { ...next[index], [key]: value };
        form.setData('items', next);
    };
    const addItem = () => form.setData('items', [...items, { category_id: categories[0]?.id ?? '', description: '', quantity: '1', unit: '', unit_price: '0', notes: '' }]);
    const removeItem = (index: number) => form.setData('items', items.filter((_: any, i: number) => i !== index));

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit(); }} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
                <div><Label>Koperasi</Label><Select value={form.data.cooperative_id || undefined} onValueChange={(v) => form.setData('cooperative_id', v)}><SelectTrigger className="w-full"><SelectValue placeholder="Pilih koperasi" /></SelectTrigger><SelectContent>{cooperatives.map((c: any) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select></div>
                <div><Label>Judul</Label><Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} /></div>
                <div><Label>Tanggal dibutuhkan</Label><Input type="date" value={form.data.needed_date ?? ''} onChange={(e) => form.setData('needed_date', e.target.value)} /></div>
                <div><Label>Catatan</Label><Input value={form.data.notes ?? ''} onChange={(e) => form.setData('notes', e.target.value)} /></div>
            </div>
            <div><Label>Tujuan penggunaan dana</Label><textarea className="min-h-28 w-full rounded-md border bg-background p-3 text-sm" value={form.data.purpose} onChange={(e) => form.setData('purpose', e.target.value)} /></div>
            <div className="space-y-3">
                <div className="flex items-center justify-between"><h2 className="font-semibold">Item kebutuhan</h2><Button type="button" variant="outline" onClick={addItem}>Tambah item</Button></div>
                {items.map((item: any, index: number) => (
                    <div key={index} className="grid gap-2 rounded-md border p-3 md:grid-cols-6">
                        <Select value={item.category_id || undefined} onValueChange={(v) => updateItem(index, 'category_id', v)}><SelectTrigger className="w-full md:col-span-2"><SelectValue placeholder="Kategori" /></SelectTrigger><SelectContent>{categories.map((c: any) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select>
                        <Input className="md:col-span-4" placeholder="Deskripsi" value={item.description} onChange={(e) => updateItem(index, 'description', e.target.value)} />
                        <Input value={item.quantity} onChange={(e) => updateItem(index, 'quantity', e.target.value)} />
                        <Input placeholder="Satuan" value={item.unit ?? ''} onChange={(e) => updateItem(index, 'unit', e.target.value)} />
                        <Input value={item.unit_price} onChange={(e) => updateItem(index, 'unit_price', e.target.value)} />
                        <div className="pt-2 text-sm font-medium md:col-span-2">{Number(item.quantity || 0) * Number(item.unit_price || 0)}</div>
                        <Button type="button" variant="outline" onClick={() => removeItem(index)}>Hapus</Button>
                    </div>
                ))}
            </div>
            <SubmissionSummary total={total} />
            <Button type="submit">{submitLabel}</Button>
        </form>
    );
}
