import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function CooperativesImport({ provinces }: any) {
    const form = useForm({
        file: null as File | null,
        province_id: 'auto',
        dry_run: false as boolean,
    });

    return (
        <div className="max-w-2xl space-y-4 p-4">
            <Head title="Import Cooperatives" />
            <div>
                <h1 className="text-2xl font-semibold">Import Cooperatives</h1>
                <p className="text-sm text-muted-foreground">
                    Format XLSX: NIK, nama, desa, kecamatan, Kota/Kabupaten,
                    latitude, longitude. Jika file tidak memiliki kolom
                    provinsi, pilih provinsi default atau biarkan otomatis bila
                    kota/kabupaten unik.
                </p>
            </div>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.transform((data) => ({
                        ...data,
                        province_id:
                            data.province_id === 'auto'
                                ? ''
                                : data.province_id,
                    }));
                    form.post('/cooperatives/import', {
                        forceFormData: true,
                    });
                }}
                className="space-y-4"
            >
                <div>
                    <Label>File Excel</Label>
                    <Input
                        type="file"
                        accept=".xlsx"
                        onChange={(event) =>
                            form.setData('file', event.target.files?.[0] ?? null)
                        }
                    />
                </div>
                <div>
                    <Label>Provinsi default</Label>
                    <Select
                        value={form.data.province_id}
                        onValueChange={(value) =>
                            form.setData('province_id', value)
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Otomatis" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="auto">Otomatis</SelectItem>
                            {provinces.map((province: any) => (
                                <SelectItem
                                    key={province.id}
                                    value={province.id}
                                >
                                    {province.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.dry_run}
                        onChange={(event) =>
                            form.setData('dry_run', event.target.checked)
                        }
                    />
                    Dry run
                </label>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Import
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/cooperatives">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
