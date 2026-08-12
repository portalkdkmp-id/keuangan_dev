import { Head, Link, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { BackButton } from '@/components/back-button';
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

export default function Form({ pic, cities }: any) {
    const form = useForm({
        name: pic?.name ?? '',
        email: pic?.email ?? '',
        phone: pic?.phone ?? '',
        city_id: pic?.city_id ?? '',
        password: '',
        is_active: pic ? !!pic.is_active : true,
    });
    const submit = () =>
        pic ? form.put(`/pics/${pic.id}`) : form.post('/pics');
    return (
        <div className="mx-auto max-w-2xl space-y-5 p-4 sm:p-6">
            <Head title={pic ? 'Edit PIC' : 'Tambah PIC'} />
            <BackButton fallback="/pics" />
            <header>
                <h1 className="text-2xl font-semibold">
                    {pic ? 'Edit PIC' : 'Tambah PIC'}
                </h1>
                <p className="text-sm text-muted-foreground">
                    Role user ditetapkan otomatis sebagai PIC KDKMP.
                </p>
            </header>
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
            >
                <div>
                    <Label>Nama</Label>
                    <Input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                    <p className="text-sm text-destructive">
                        {form.errors.name}
                    </p>
                </div>
                <div>
                    <Label>Email</Label>
                    <Input
                        type="email"
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                    />
                    <p className="text-sm text-destructive">
                        {form.errors.email}
                    </p>
                </div>
                <div>
                    <Label>Nomor telepon</Label>
                    <Input
                        value={form.data.phone}
                        onChange={(event) =>
                            form.setData('phone', event.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Wilayah kabupaten/kota</Label>
                    <Select
                        value={form.data.city_id || undefined}
                        onValueChange={(value) =>
                            form.setData('city_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih wilayah" />
                        </SelectTrigger>
                        <SelectContent>
                            {cities.map((city: any) => (
                                <SelectItem key={city.id} value={city.id}>
                                    {city.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p className="text-sm text-destructive">
                        {form.errors.city_id}
                    </p>
                </div>
                <div>
                    <Label>
                        {pic ? 'Password baru (opsional)' : 'Password'}
                    </Label>
                    <Input
                        type="password"
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                    <p className="text-sm text-destructive">
                        {form.errors.password}
                    </p>
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_active}
                        onCheckedChange={(checked) =>
                            form.setData('is_active', checked === true)
                        }
                    />{' '}
                    Aktif
                </label>
                <div className="flex flex-wrap justify-between gap-2">
                    <div className="flex gap-2">
                        <Button disabled={form.processing}>Simpan</Button>
                        <Button asChild variant="outline">
                            <Link href="/pics">Batal</Link>
                        </Button>
                    </div>
                    {pic && (
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() =>
                                confirm(`Hapus PIC ${pic.name}?`) &&
                                router.delete(`/pics/${pic.id}`)
                            }
                        >
                            <Trash2 className="size-4" /> Hapus
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
