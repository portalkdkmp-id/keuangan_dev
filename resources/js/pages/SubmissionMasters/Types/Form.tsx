import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function SubmissionTypeForm({ type, requestCategories }: any) {
    const form = useForm({
        name: type?.name ?? '',
        submission_request_category_id:
            type?.submission_request_category_id ?? '',
        sort_order: type?.sort_order ?? 0,
        is_active: type?.is_active ?? true,
    });
    const submit = () =>
        type
            ? form.put(`/submission-types/${type.id}`)
            : form.post('/submission-types');
    return (
        <div className="max-w-xl space-y-4 p-4">
            <Head title="Jenis Pengajuan" />
            <h1 className="text-2xl font-semibold">
                {type ? 'Edit' : 'Tambah'} Jenis Pengajuan
            </h1>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    submit();
                }}
                className="space-y-4"
            >
                <div>
                    <Label>Nama</Label>
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </div>
                <div>
                    <Label>Kategori Pengajuan</Label>
                    <Select
                        value={
                            form.data.submission_request_category_id ||
                            '__global__'
                        }
                        onValueChange={(value) =>
                            form.setData(
                                'submission_request_category_id',
                                value === '__global__' ? '' : value,
                            )
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__global__">
                                Tanpa kategori (tampil di semua kategori)
                            </SelectItem>
                            {requestCategories.map((category: any) => (
                                <SelectItem
                                    key={category.id}
                                    value={category.id}
                                >
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Urutan</Label>
                    <Input
                        type="number"
                        value={form.data.sort_order}
                        onChange={(e) =>
                            form.setData('sort_order', e.target.value)
                        }
                    />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_active}
                        onCheckedChange={(checked) =>
                            form.setData('is_active', Boolean(checked))
                        }
                    />{' '}
                    Aktif
                </label>
                <div className="flex gap-2">
                    <Button>Simpan</Button>
                    <Button variant="outline" asChild>
                        <Link href="/submission-types">Batal</Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
