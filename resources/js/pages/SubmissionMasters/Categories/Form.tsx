import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function SubmissionCategoryForm({ category }: any) {
    const form = useForm({ name: category?.name ?? '', sort_order: category?.sort_order ?? 0, is_active: category?.is_active ?? true });
    const submit = () => category ? form.put(`/submission-categories/${category.id}`) : form.post('/submission-categories');
    return <div className="max-w-xl space-y-4 p-4"><Head title="Kategori Pengajuan" /><h1 className="text-2xl font-semibold">{category ? 'Edit' : 'Tambah'} Kategori Pengajuan</h1>
        <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-4">
            <div><Label>Nama</Label><Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /></div>
            <div><Label>Urutan</Label><Input type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', e.target.value)} /></div>
            <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.is_active} onCheckedChange={(checked) => form.setData('is_active', Boolean(checked))} /> Aktif</label>
            <div className="flex gap-2"><Button>Simpan</Button><Button variant="outline" asChild><Link href="/submission-categories">Batal</Link></Button></div>
        </form>
    </div>;
}
