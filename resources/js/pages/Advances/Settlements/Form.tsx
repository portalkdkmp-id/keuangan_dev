import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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

const emptyItem = () => ({
    expense_date: '',
    description: '',
    category_id: '',
    amount: '',
    vendor_name: '',
    invoice_number: '',
    payment_method: 'bank_transfer',
    payment_reference: '',
    notes: '',
});

export default function Form({ advance, report, categories }: any) {
    const [step, setStep] = useState(1);
    const form = useForm({
        summary: report?.summary ?? '',
        usage_date_from: report?.usage_date_from?.slice(0, 10) ?? '',
        usage_date_to: report?.usage_date_to?.slice(0, 10) ?? '',
        items: report?.items?.map((item: any) => ({
            ...item,
            expense_date: item.expense_date?.slice(0, 10),
        })) ?? [emptyItem()],
        purchase_proofs: [] as File[][],
        payment_proofs: [] as File[][],
    });
    const setItem = (index: number, key: string, value: string) =>
        form.setData(
            'items',
            form.data.items.map((item: any, itemIndex: number) =>
                itemIndex === index ? { ...item, [key]: value } : item,
            ),
        );
    const setFiles = (
        key: 'purchase_proofs' | 'payment_proofs',
        index: number,
        files: File[],
    ) => {
        const current = [...form.data[key]];
        current[index] = files;
        form.setData(key, current);
    };
    const total = form.data.items.reduce(
        (sum: number, item: any) => sum + Number(item.amount || 0),
        0,
    );
    const submit = () =>
        report
            ? form.put(`/advance-settlements/${report.id}`, {
                  forceFormData: true,
              })
            : form.post(`/advance-settlements/${advance.id}`, {
                  forceFormData: true,
              });

    return (
        <div className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6">
            <Head title="Settlement Uang Panjar" />
            <BackButton fallback="/advance-settlements" />
            <header>
                <h1 className="text-2xl font-semibold">
                    Settlement Uang Panjar
                </h1>
                <p className="text-sm text-muted-foreground">
                    {advance.submission.submission_number} · Dana diterima{' '}
                    {rupiah(advance.disbursed_amount)}
                </p>
            </header>
            <div
                className="grid grid-cols-4 gap-2"
                aria-label="Tahapan settlement"
            >
                {['Ringkasan', 'Transaksi', 'Bukti', 'Review'].map(
                    (label, index) => (
                        <div
                            key={label}
                            className={`border-b-2 pb-2 text-center text-xs sm:text-sm ${step === index + 1 ? 'border-primary font-medium text-primary' : 'border-muted text-muted-foreground'}`}
                        >
                            {index + 1}. {label}
                        </div>
                    ),
                )}
            </div>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
            >
                {step === 1 && (
                    <section className="space-y-4">
                        <div>
                            <Label>Ringkasan penggunaan</Label>
                            <Textarea
                                className="min-h-32"
                                value={form.data.summary}
                                onChange={(event) =>
                                    form.setData('summary', event.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label>Tanggal transaksi mulai</Label>
                                <Input
                                    type="date"
                                    value={form.data.usage_date_from}
                                    onChange={(event) =>
                                        form.setData(
                                            'usage_date_from',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Tanggal transaksi selesai</Label>
                                <Input
                                    type="date"
                                    value={form.data.usage_date_to}
                                    onChange={(event) =>
                                        form.setData(
                                            'usage_date_to',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </section>
                )}
                {step === 2 && (
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold">Transaksi aktual</h2>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    form.setData('items', [
                                        ...form.data.items,
                                        emptyItem(),
                                    ])
                                }
                            >
                                <Plus className="size-4" /> Tambah
                            </Button>
                        </div>
                        {form.data.items.map((item: any, index: number) => (
                            <div
                                key={index}
                                className="grid gap-3 border-b pb-5 sm:grid-cols-2"
                            >
                                <div>
                                    <Label>Tanggal transaksi</Label>
                                    <Input
                                        type="date"
                                        value={item.expense_date}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'expense_date',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Jenis pengeluaran</Label>
                                    <Select
                                        value={item.category_id}
                                        onValueChange={(value) =>
                                            setItem(index, 'category_id', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih jenis" />
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
                                    <Label>Vendor / penyedia</Label>
                                    <Input
                                        value={item.vendor_name}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'vendor_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Nominal aktual</Label>
                                    <MoneyInput
                                        value={item.amount}
                                        onChange={(value) =>
                                            setItem(index, 'amount', value)
                                        }
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <Label>Deskripsi</Label>
                                    <Textarea
                                        value={item.description}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Nomor invoice / kuitansi</Label>
                                    <Input
                                        value={item.invoice_number}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'invoice_number',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Metode pembayaran</Label>
                                    <Select
                                        value={item.payment_method}
                                        onValueChange={(value) =>
                                            setItem(
                                                index,
                                                'payment_method',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="bank_transfer">
                                                Transfer bank
                                            </SelectItem>
                                            <SelectItem value="cash">
                                                Tunai
                                            </SelectItem>
                                            <SelectItem value="debit_card">
                                                Kartu debit
                                            </SelectItem>
                                            <SelectItem value="credit_card">
                                                Kartu kredit
                                            </SelectItem>
                                            <SelectItem value="e_wallet">
                                                E-wallet
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Lainnya
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Nomor referensi</Label>
                                    <Input
                                        value={item.payment_reference}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'payment_reference',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Catatan</Label>
                                    <Input
                                        value={item.notes}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'notes',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                {form.data.items.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        className="w-fit"
                                        onClick={() =>
                                            form.setData(
                                                'items',
                                                form.data.items.filter(
                                                    (
                                                        _: any,
                                                        itemIndex: number,
                                                    ) => itemIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" /> Hapus
                                    </Button>
                                )}
                            </div>
                        ))}
                    </section>
                )}
                {step === 3 && (
                    <section className="space-y-5">
                        {form.data.items.map((item: any, index: number) => (
                            <div
                                key={index}
                                className="space-y-3 border-b pb-5"
                            >
                                <h2 className="font-medium">
                                    {index + 1}.{' '}
                                    {item.description || 'Transaksi'}
                                </h2>
                                <div>
                                    <Label>Bukti pembelian / sewa</Label>
                                    <Input
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        onChange={(event) =>
                                            setFiles(
                                                'purchase_proofs',
                                                index,
                                                Array.from(
                                                    event.target.files ?? [],
                                                ),
                                            )
                                        }
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {report?.items?.[
                                            index
                                        ]?.attachments?.filter(
                                            (file: any) =>
                                                file.attachment_type ===
                                                'purchase_proof',
                                        ).length
                                            ? 'Bukti tersimpan akan dipertahankan jika tidak memilih file baru.'
                                            : 'Wajib minimal satu file.'}
                                    </p>
                                </div>
                                <div>
                                    <Label>Bukti pembayaran</Label>
                                    <Input
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        onChange={(event) =>
                                            setFiles(
                                                'payment_proofs',
                                                index,
                                                Array.from(
                                                    event.target.files ?? [],
                                                ),
                                            )
                                        }
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {report?.items?.[
                                            index
                                        ]?.attachments?.filter(
                                            (file: any) =>
                                                file.attachment_type ===
                                                'payment_proof',
                                        ).length
                                            ? 'Bukti tersimpan akan dipertahankan jika tidak memilih file baru.'
                                            : 'Wajib minimal satu file.'}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </section>
                )}
                {step === 4 && (
                    <section className="space-y-4">
                        <h2 className="font-semibold">Review settlement</h2>
                        <dl className="grid gap-3 border-y py-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    Panjar diterima
                                </dt>
                                <dd className="font-medium">
                                    {rupiah(advance.disbursed_amount)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Total realisasi
                                </dt>
                                <dd className="font-medium">{rupiah(total)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Selisih
                                </dt>
                                <dd className="font-medium">
                                    {total <= Number(advance.disbursed_amount)
                                        ? `Sisa ${rupiah(Number(advance.disbursed_amount) - total)}`
                                        : `Kekurangan ${rupiah(total - Number(advance.disbursed_amount))}`}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Jumlah transaksi
                                </dt>
                                <dd>{form.data.items.length} item</dd>
                            </div>
                        </dl>
                        <p className="text-sm">{form.data.summary}</p>
                    </section>
                )}
                <div className="mt-6 flex justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={step === 1}
                        onClick={() => setStep(step - 1)}
                    >
                        <ArrowLeft className="size-4" /> Kembali
                    </Button>
                    {step < 4 ? (
                        <Button type="button" onClick={() => setStep(step + 1)}>
                            Lanjut <ArrowRight className="size-4" />
                        </Button>
                    ) : (
                        <Button disabled={form.processing}>
                            {form.processing ? 'Menyimpan...' : 'Simpan Draft'}
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
