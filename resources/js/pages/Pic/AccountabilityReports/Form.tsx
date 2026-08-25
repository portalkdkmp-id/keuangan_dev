import { Head, useForm } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
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
import { Textarea } from '@/components/ui/textarea';
import { MultipleFileInput } from '@/components/multiple-file-input';
const emptyItem = () => ({
    expense_date: '',
    description: '',
    category_id: '',
    amount: '',
    vendor_name: '',
    invoice_number: '',
    notes: '',
});
export default function Form({
    submission,
    report,
    receivedAmount,
    categories,
}: any) {
    const form = useForm({
        summary: report?.summary ?? '',
        usage_date_from: report?.usage_date_from?.slice(0, 10) ?? '',
        usage_date_to: report?.usage_date_to?.slice(0, 10) ?? '',
        items: report?.items?.map((item: any) => ({
            ...item,
            expense_date: item.expense_date?.slice(0, 10),
        })) ?? [emptyItem()],
        attachment_type: 'receipt',
        attachments: [] as File[],
    });
    const setItem = (index: number, key: string, value: string) =>
        form.setData(
            'items',
            form.data.items.map((item: any, i: number) =>
                i === index ? { ...item, [key]: value } : item,
            ),
        );
    const submit = () =>
        report
            ? form.put(`/accountability-reports/${report.id}`, {
                  forceFormData: true,
              })
            : form.post(`/accountability-reports/${submission.id}`, {
                  forceFormData: true,
              });
    return (
        <div className="mx-auto w-full space-y-4 p-4">
            <Head title="Laporan Penggunaan Dana" />
            <BackButton fallback="/accountability-reports" />
            <div>
                <h1 className="text-2xl font-semibold">
                    Laporan Penggunaan Dana
                </h1>
                <p className="text-sm text-muted-foreground">
                    {submission.submission_number} · Dana diterima{' '}
                    {rupiah(receivedAmount)}
                </p>
            </div>
            <form
                className="space-y-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    submit();
                }}
            >
                <div className="space-y-3 rounded-md border p-4">
                    <Label>Ringkasan penggunaan</Label>
                    <Textarea
                        className="min-h-28"
                        value={form.data.summary}
                        onChange={(e) =>
                            form.setData('summary', e.target.value)
                        }
                    />
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div>
                            <Label>Tanggal penggunaan mulai</Label>
                            <Input
                                type="date"
                                value={form.data.usage_date_from}
                                onChange={(e) =>
                                    form.setData(
                                        'usage_date_from',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <Label>Tanggal penggunaan selesai</Label>
                            <Input
                                type="date"
                                value={form.data.usage_date_to}
                                onChange={(e) =>
                                    form.setData(
                                        'usage_date_to',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                </div>
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-semibold">Item Realisasi</h2>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                form.setData('items', [
                                    ...form.data.items,
                                    emptyItem(),
                                ])
                            }
                        >
                            Tambah Item
                        </Button>
                    </div>
                    {form.data.items.map((item: any, index: number) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-md border p-4 sm:grid-cols-2"
                        >
                            <div>
                                <Label>Tanggal</Label>
                                <Input
                                    type="date"
                                    value={item.expense_date}
                                    onChange={(e) =>
                                        setItem(
                                            index,
                                            'expense_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Deskripsi</Label>
                                <Input
                                    value={item.description}
                                    onChange={(e) =>
                                        setItem(
                                            index,
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Kategori</Label>
                                <Select
                                    value={item.category_id}
                                    onValueChange={(value) =>
                                        setItem(index, 'category_id', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category: any) => (
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
                                <Label>Nominal</Label>
                                <MoneyInput
                                    value={item.amount}
                                    onChange={(value) =>
                                        setItem(index, 'amount', value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Vendor</Label>
                                <Input
                                    value={item.vendor_name}
                                    onChange={(e) =>
                                        setItem(
                                            index,
                                            'vendor_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Nomor invoice</Label>
                                <Input
                                    value={item.invoice_number}
                                    onChange={(e) =>
                                        setItem(
                                            index,
                                            'invoice_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>Catatan</Label>
                                <Textarea
                                    value={item.notes}
                                    onChange={(e) =>
                                        setItem(index, 'notes', e.target.value)
                                    }
                                />
                            </div>
                            {form.data.items.length > 1 && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() =>
                                        form.setData(
                                            'items',
                                            form.data.items.filter(
                                                (_: any, i: number) =>
                                                    i !== index,
                                            ),
                                        )
                                    }
                                >
                                    Hapus Item
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
                <div className="space-y-3 rounded-md border p-4">
                    <Label>Jenis bukti</Label>
                    <Select
                        value={form.data.attachment_type}
                        onValueChange={(value) =>
                            form.setData('attachment_type', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="invoice">Invoice</SelectItem>
                            <SelectItem value="receipt">
                                Kuitansi / Struk
                            </SelectItem>
                            <SelectItem value="photo">Foto</SelectItem>
                            <SelectItem value="handover_document">
                                Dokumen Serah Terima
                            </SelectItem>
                            <SelectItem value="activity_report">
                                Laporan Kegiatan
                            </SelectItem>
                            <SelectItem value="other">Lainnya</SelectItem>
                        </SelectContent>
                    </Select>
                    <MultipleFileInput
                        label="Upload bukti"
                        files={form.data.attachments}
                        onFiles={(files) => form.setData('attachments', files)}
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                    />
                </div>
                <Button disabled={form.processing}>
                    {form.processing ? 'Menyimpan...' : 'Simpan Draft'}
                </Button>
            </form>
        </div>
    );
}
