import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight, Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
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
import { CooperativeCombobox } from '@/components/cooperative-combobox';
import { Checkbox } from '@/components/ui/checkbox';
const emptyExpense = () => ({
    expense_date: '',
    expense_type_id: '',
    vendor_name: '',
    description: '',
    actual_amount: '',
    payment_method: 'bank_transfer',
    payment_reference: '',
    notes: '',
});
export default function Form({
    submission,
    cooperatives,
    bankAccounts,
    expenseTypes,
    canSubmitInternal,
}: any) {
    const detail = submission?.reimbursement_detail;
    const [step, setStep] = useState(1);
    const form = useForm<any>({
        title: submission?.title ?? '',
        cooperative_id: submission?.cooperative_id ?? '',
        is_urgent: submission?.is_urgent ?? false,
        claimant_bank_account_id: detail?.claimant_bank_account_id ?? '',
        summary: detail?.summary ?? '',
        expenses: detail?.expenses?.map((e: any) => ({
            ...e,
            actual_amount: String(Math.trunc(Number(e.actual_amount))),
        })) ?? [emptyExpense()],
        purchase_proofs: [] as File[][],
        payment_proofs: [] as File[][],
    });
    const total = useMemo(
        () =>
            form.data.expenses.reduce(
                (n: number, e: any) => n + Number(e.actual_amount || 0),
                0,
            ),
        [form.data.expenses],
    );
    const setExpense = (i: number, k: string, v: any) =>
        form.setData(
            'expenses',
            form.data.expenses.map((e: any, x: number) =>
                x === i ? { ...e, [k]: v } : e,
            ),
        );
    const setProof = (
        key: 'purchase_proofs' | 'payment_proofs',
        index: number,
        files: File[],
    ) => {
        const values = [...form.data[key]];
        values[index] = files;
        form.setData(key, values);
    };
    const submit = () => {
        const options: any = {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors: Record<string, string>) => {
                const keys = Object.keys(errors);
                const targetStep = keys.some(
                    (key) =>
                        key.startsWith('purchase_proofs') ||
                        key.startsWith('payment_proofs'),
                )
                    ? 3
                    : keys.some((key) => key.startsWith('expenses'))
                      ? 2
                      : 1;
                setStep(targetStep);
                toast.error('Reimbursement belum dapat disimpan', {
                    description:
                        Object.values(errors)[0] ??
                        'Periksa kembali data yang wajib diisi.',
                });
            },
        };
        submission
            ? router.post(
                  `/reimbursements/${submission.id}`,
                  { ...form.data, _method: 'put' },
                  options,
              )
            : form.post('/reimbursements', options);
    };
    return (
        <div className="mx-auto w-full space-y-5 p-4 md:w-3/4">
            <Head
                title={submission ? 'Edit Reimbursement' : 'Buat Reimbursement'}
            />
            <BackButton fallback="/reimbursements" />
            <div>
                <h1 className="text-2xl font-semibold">
                    {submission ? 'Edit' : 'Buat'} Reimbursement
                </h1>
                <p className="text-sm text-muted-foreground">
                    Langkah {step} dari 4
                </p>
            </div>
            <div className="grid grid-cols-4 gap-2">
                {['Informasi', 'Transaksi', 'Bukti', 'Review'].map((x, i) => (
                    <div
                        key={x}
                        className={`border-b-2 pb-2 text-center text-xs ${step === i + 1 ? 'border-primary font-medium' : 'text-muted-foreground'}`}
                    >
                        {x}
                    </div>
                ))}
            </div>
            {Object.keys(form.errors).length > 0 && (
                <div
                    className="border-l-4 border-destructive bg-destructive/5 p-3 text-sm"
                    role="alert"
                >
                    <p className="font-medium text-destructive">
                        Periksa kembali data berikut:
                    </p>
                    <ul className="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                        {Object.entries(form.errors)
                            .slice(0, 6)
                            .map(([field, message]) => (
                                <li key={field}>{String(message)}</li>
                            ))}
                    </ul>
                </div>
            )}
            {step === 1 && (
                <section className="space-y-4">
                    <div>
                        <Label>Judul</Label>
                        <Input
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                    </div>
                    <div>
                        <Label>
                            Koperasi{canSubmitInternal ? ' (opsional)' : ''}
                        </Label>
                        <CooperativeCombobox
                            cooperatives={cooperatives}
                            value={form.data.cooperative_id}
                            allowInternal={canSubmitInternal}
                            onValueChange={(value) =>
                                form.setData('cooperative_id', value)
                            }
                        />
                    </div>
                    <div className="flex items-center gap-3 rounded-md border p-3">
                        <Checkbox
                            id="reimbursement-is-urgent"
                            checked={form.data.is_urgent}
                            onCheckedChange={(value) =>
                                form.setData('is_urgent', value === true)
                            }
                        />
                        <Label htmlFor="reimbursement-is-urgent">
                            Pengajuan Urgent
                        </Label>
                    </div>
                    <div>
                        <Label>Rekening tujuan</Label>
                        <Select
                            value={form.data.claimant_bank_account_id}
                            onValueChange={(v) =>
                                form.setData('claimant_bank_account_id', v)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pilih rekening" />
                            </SelectTrigger>
                            <SelectContent>
                                {bankAccounts.map((x: any) => (
                                    <SelectItem key={x.id} value={x.id}>
                                        {x.bank_name} - {x.account_number} (
                                        {x.account_holder_name})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Ringkasan</Label>
                        <Textarea
                            value={form.data.summary}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                        />
                    </div>
                </section>
            )}
            {step === 2 && (
                <section className="space-y-4">
                    {form.data.expenses.map((e: any, i: number) => (
                        <div
                            key={i}
                            className="space-y-3 rounded-md border p-4"
                        >
                            <div className="flex justify-between">
                                <h2 className="font-medium">
                                    Transaksi {i + 1}
                                </h2>
                                {form.data.expenses.length > 1 && (
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        onClick={() =>
                                            form.setData(
                                                'expenses',
                                                form.data.expenses.filter(
                                                    (_: any, x: number) =>
                                                        x !== i,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                )}
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <Label>Tanggal transaksi</Label>
                                    <Input
                                        type="date"
                                        value={e.expense_date}
                                        onChange={(x) =>
                                            setExpense(
                                                i,
                                                'expense_date',
                                                x.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Jenis pengeluaran</Label>
                                    <Select
                                        value={e.expense_type_id}
                                        onValueChange={(v) =>
                                            setExpense(i, 'expense_type_id', v)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Pilih jenis" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {expenseTypes.map((x: any) => (
                                                <SelectItem
                                                    key={x.id}
                                                    value={x.id}
                                                >
                                                    {x.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div>
                                <Label>Vendor / merchant</Label>
                                <Input
                                    value={e.vendor_name}
                                    onChange={(x) =>
                                        setExpense(
                                            i,
                                            'vendor_name',
                                            x.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Deskripsi</Label>
                                <Textarea
                                    value={e.description}
                                    onChange={(x) =>
                                        setExpense(
                                            i,
                                            'description',
                                            x.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Nominal aktual</Label>
                                <MoneyInput
                                    value={e.actual_amount}
                                    onChange={(v) =>
                                        setExpense(i, 'actual_amount', v)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Metode pembayaran</Label>
                                <Select
                                    value={e.payment_method}
                                    onValueChange={(v) =>
                                        setExpense(i, 'payment_method', v)
                                    }
                                >
                                    <SelectTrigger className="w-full">
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
                                <Label>Referensi pembayaran</Label>
                                <Input
                                    value={e.payment_reference}
                                    onChange={(x) =>
                                        setExpense(
                                            i,
                                            'payment_reference',
                                            x.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    ))}
                    <Button
                        variant="outline"
                        onClick={() =>
                            form.setData('expenses', [
                                ...form.data.expenses,
                                emptyExpense(),
                            ])
                        }
                    >
                        <Plus />
                        Tambah transaksi
                    </Button>
                </section>
            )}
            {step === 3 && (
                <section className="space-y-4">
                    {form.data.expenses.map((e: any, i: number) => (
                        <div
                            key={i}
                            className="space-y-4 rounded-md border p-4"
                        >
                            <h2 className="font-medium">
                                {e.vendor_name || `Transaksi ${i + 1}`}
                            </h2>
                            <div>
                                <MultipleFileInput
                                    label="Bukti pembelian atau sewa"
                                    files={form.data.purchase_proofs[i] ?? []}
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    description="Invoice, nota, kuitansi, kontrak sewa, atau tagihan."
                                    onFiles={(files) =>
                                        setProof('purchase_proofs', i, files)
                                    }
                                />
                            </div>
                            <div>
                                <MultipleFileInput
                                    label="Bukti pembayaran"
                                    files={form.data.payment_proofs[i] ?? []}
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    description="Bukti transfer, mutasi rekening, atau struk pembayaran."
                                    onFiles={(files) =>
                                        setProof('payment_proofs', i, files)
                                    }
                                />
                            </div>
                        </div>
                    ))}
                </section>
            )}
            {step === 4 && (
                <section className="space-y-4">
                    <div className="rounded-md border p-4">
                        <h2 className="font-semibold">{form.data.title}</h2>
                        <p className="text-sm text-muted-foreground">
                            {form.data.expenses.length} transaksi
                        </p>
                        <div className="mt-4 text-2xl font-semibold">
                            {rupiah(total)}
                        </div>
                    </div>
                    {form.data.expenses.map((e: any, i: number) => (
                        <div
                            key={i}
                            className="flex justify-between border-b pb-3 text-sm"
                        >
                            <div>
                                <div className="font-medium">
                                    {e.vendor_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {e.description}
                                </div>
                            </div>
                            <div>{rupiah(e.actual_amount)}</div>
                        </div>
                    ))}
                </section>
            )}
            <div className="flex justify-between border-t pt-4">
                <Button
                    type="button"
                    variant="outline"
                    disabled={step === 1}
                    onClick={() => setStep((x) => x - 1)}
                >
                    <ChevronLeft />
                    Kembali
                </Button>
                {step < 4 ? (
                    <Button type="button" onClick={() => setStep((x) => x + 1)}>
                        Lanjut
                        <ChevronRight />
                    </Button>
                ) : (
                    <Button
                        type="button"
                        disabled={form.processing}
                        onClick={submit}
                    >
                        {form.processing ? 'Menyimpan...' : 'Simpan Draft'}
                    </Button>
                )}
            </div>
        </div>
    );
}
