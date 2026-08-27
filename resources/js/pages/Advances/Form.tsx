import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
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
export default function Form({
    submission,
    cooperatives,
    bankAccounts,
    defaultSettlementDays,
    canSubmitInternal,
}: any) {
    const d = submission?.advance_detail;
    const [step, setStep] = useState(1);
    const defaultDate = useMemo(() => {
        const x = new Date();
        x.setDate(x.getDate() + defaultSettlementDays);
        return x.toISOString().slice(0, 10);
    }, [defaultSettlementDays]);
    const f = useForm<any>({
        title: submission?.title ?? '',
        cooperative_id: submission?.cooperative_id ?? '',
        is_urgent: submission?.is_urgent ?? false,
        purpose: d?.purpose ?? '',
        estimated_amount: d?.estimated_amount
            ? String(Math.trunc(Number(d.estimated_amount)))
            : '',
        expected_transaction_date:
            d?.expected_transaction_date?.slice(0, 10) ?? '',
        expected_settlement_date:
            d?.expected_settlement_date?.slice(0, 10) ?? defaultDate,
        recipient_bank_account_id: d?.recipient_bank_account_id ?? '',
        notes: d?.notes ?? '',
        attachments: [] as File[],
    });
    const save = () => {
        const options: any = {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors: Record<string, string>) => {
                const keys = Object.keys(errors);
                const targetStep = keys.some((key) =>
                    key.startsWith('attachments'),
                )
                    ? 3
                    : keys.some((key) =>
                            [
                                'estimated_amount',
                                'expected_transaction_date',
                                'expected_settlement_date',
                                'recipient_bank_account_id',
                                'notes',
                            ].includes(key),
                        )
                      ? 2
                      : 1;
                setStep(targetStep);
                toast.error('Uang panjar belum dapat disimpan', {
                    description:
                        Object.values(errors)[0] ??
                        'Periksa kembali data yang wajib diisi.',
                });
            },
        };
        if (submission) {
            f.transform((data: any) => ({ ...data, _method: 'put' }));
            f.post(`/advances/${submission.id}`, options);
            return;
        }
        f.post('/advances', options);
    };
    return (
        <div className="mx-auto w-full space-y-5 p-4 md:w-3/4">
            <Head title="Uang Panjar" />
            <BackButton fallback="/submissions" />
            <div>
                <h1 className="text-2xl font-semibold">
                    {submission ? 'Edit' : 'Buat'} Uang Panjar
                </h1>
                <p className="text-sm text-muted-foreground">
                    Langkah {step} dari 4
                </p>
            </div>
            <div className="grid grid-cols-4 gap-2">
                {['Informasi', 'Estimasi', 'Dokumen', 'Review'].map((x, i) => (
                    <div
                        key={x}
                        className={`border-b-2 pb-2 text-center text-xs ${step === i + 1 ? 'border-primary font-medium' : 'text-muted-foreground'}`}
                    >
                        {x}
                    </div>
                ))}
            </div>
            {Object.keys(f.errors).length > 0 && (
                <div
                    className="border-l-4 border-destructive bg-destructive/5 p-3 text-sm"
                    role="alert"
                >
                    <p className="font-medium text-destructive">
                        Periksa kembali data berikut:
                    </p>
                    <ul className="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                        {Object.entries(f.errors)
                            .slice(0, 6)
                            .map(([field, message]) => (
                                <li key={field}>{String(message)}</li>
                            ))}
                    </ul>
                </div>
            )}
            {step === 1 && (
                <div className="space-y-4">
                    <div>
                        <Label>Judul</Label>
                        <Input
                            value={f.data.title}
                            onChange={(e) => f.setData('title', e.target.value)}
                        />
                    </div>
                    <div>
                        <Label>
                            Koperasi{canSubmitInternal ? ' (opsional)' : ''}
                        </Label>
                        <CooperativeCombobox
                            cooperatives={cooperatives}
                            value={f.data.cooperative_id}
                            allowInternal={canSubmitInternal}
                            onValueChange={(value) =>
                                f.setData('cooperative_id', value)
                            }
                        />
                    </div>
                    <div className="flex items-center gap-3 rounded-md border p-3">
                        <Checkbox
                            id="advance-is-urgent"
                            checked={f.data.is_urgent}
                            onCheckedChange={(value) =>
                                f.setData('is_urgent', value === true)
                            }
                        />
                        <Label htmlFor="advance-is-urgent">
                            Pengajuan Urgent
                        </Label>
                    </div>
                    <div>
                        <Label>Tujuan penggunaan</Label>
                        <Textarea
                            value={f.data.purpose}
                            onChange={(e) =>
                                f.setData('purpose', e.target.value)
                            }
                        />
                    </div>
                    <div>
                        <Label>Penanggung jawab</Label>
                        <Input value="Saya sendiri (Finance Staff)" disabled />
                    </div>
                </div>
            )}
            {step === 2 && (
                <div className="space-y-4">
                    <div>
                        <Label>Nominal estimasi</Label>
                        <MoneyInput
                            value={f.data.estimated_amount}
                            onChange={(v) => f.setData('estimated_amount', v)}
                        />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div>
                            <Label>Tanggal estimasi transaksi</Label>
                            <Input
                                type="date"
                                value={f.data.expected_transaction_date}
                                onChange={(e) =>
                                    f.setData(
                                        'expected_transaction_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <Label>Deadline pertanggungjawaban</Label>
                            <Input
                                type="date"
                                value={f.data.expected_settlement_date}
                                onChange={(e) =>
                                    f.setData(
                                        'expected_settlement_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div>
                        <Label>Rekening penerima</Label>
                        <Select
                            value={f.data.recipient_bank_account_id}
                            onValueChange={(v) =>
                                f.setData('recipient_bank_account_id', v)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pilih rekening" />
                            </SelectTrigger>
                            <SelectContent>
                                {bankAccounts.map((x: any) => (
                                    <SelectItem key={x.id} value={x.id}>
                                        {x.bank_name} - {x.account_number}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Catatan</Label>
                        <Textarea
                            value={f.data.notes}
                            onChange={(e) => f.setData('notes', e.target.value)}
                        />
                    </div>
                </div>
            )}
            {step === 3 && (
                <div className="space-y-3 rounded-md border p-4">
                    <MultipleFileInput
                        label="Dokumen pendukung"
                        files={f.data.attachments}
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.doc,.docx"
                        description="Quotation, estimasi harga, proposal, daftar kebutuhan, atau screenshot harga."
                        onFiles={(files) => f.setData('attachments', files)}
                    />
                </div>
            )}
            {step === 4 && (
                <div className="space-y-4 rounded-md border p-4">
                    <h2 className="font-semibold">{f.data.title}</h2>
                    <div className="text-2xl font-semibold">
                        {rupiah(f.data.estimated_amount)}
                    </div>
                    <p className="text-sm">{f.data.purpose}</p>
                    <div className="grid gap-2 text-sm sm:grid-cols-2">
                        <span>
                            Transaksi: {f.data.expected_transaction_date || '-'}
                        </span>
                        <span>Deadline: {f.data.expected_settlement_date}</span>
                    </div>
                </div>
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
                        disabled={f.processing}
                        onClick={save}
                    >
                        {f.processing ? 'Menyimpan...' : 'Simpan Draft'}
                    </Button>
                )}
            </div>
        </div>
    );
}
