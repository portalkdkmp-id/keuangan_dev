import { Head, Link, useForm } from '@inertiajs/react';
import { Download, Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PicImport() {
    const form = useForm({ file: null as File | null, dry_run: false });

    return (
        <div className="max-w-2xl space-y-5 p-4 sm:p-6">
            <Head title="Import PIC KDKMP" />
            <div>
                <h1 className="text-2xl font-semibold">Import PIC KDKMP</h1>
                <p className="text-sm text-muted-foreground">
                    Tambahkan atau perbarui banyak PIC sekaligus menggunakan
                    template Excel.
                </p>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>File Import</CardTitle>
                    <CardDescription>
                        Isi kolom nama, email, nomor telepon, provinsi,
                        kota/kabupaten, password, dan status aktif. Password
                        boleh kosong saat memperbarui PIC existing.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/pics/import', { forceFormData: true });
                        }}
                    >
                        <Button type="button" variant="outline" asChild>
                            <a href="/pics/import/template">
                                <Download className="size-4" />
                                Unduh Template XLSX
                            </a>
                        </Button>
                        <div className="space-y-1.5">
                            <Label htmlFor="pic-import-file">File Excel</Label>
                            <Input
                                id="pic-import-file"
                                type="file"
                                accept=".xlsx"
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                Maksimal 50 MB, format .xlsx.
                            </p>
                            {form.errors.file && (
                                <p className="text-sm text-destructive">
                                    {form.errors.file}
                                </p>
                            )}
                        </div>
                        <label className="flex items-start gap-3 border p-3">
                            <Checkbox
                                checked={form.data.dry_run}
                                onCheckedChange={(value) =>
                                    form.setData('dry_run', value === true)
                                }
                            />
                            <span>
                                <span className="block text-sm font-medium">
                                    Validasi saja (dry run)
                                </span>
                                <span className="block text-xs text-muted-foreground">
                                    Periksa isi file tanpa menyimpan perubahan.
                                </span>
                            </span>
                        </label>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                disabled={!form.data.file || form.processing}
                            >
                                <Upload className="size-4" />
                                {form.processing
                                    ? 'Memproses...'
                                    : 'Import PIC'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/pics">Kembali</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
