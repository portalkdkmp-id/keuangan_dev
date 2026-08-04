import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
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
export default function Form({
    submission,
    cooperatives,
    bankAccounts,
    defaultSettlementDays,
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
    const save = () =>
        submission
            ? router.post(
                  `/advances/${submission.id}`,
                  { ...f.data, _method: 'put' },
                  { forceFormData: true },
              )
            : f.post('/advances', { forceFormData: true });
    return (
        <div className="mx-auto max-w-3xl space-y-5 p-4">
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
                        <Label>Koperasi</Label>
                        <Select
                            value={f.data.cooperative_id}
                            onValueChange={(v) =>
                                f.setData('cooperative_id', v)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pilih koperasi" />
                            </SelectTrigger>
                            <SelectContent>
                                {cooperatives.map((x: any) => (
                                    <SelectItem key={x.id} value={x.id}>
                                        {x.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                    <Label>Dokumen pendukung</Label>
                    <p className="text-xs text-muted-foreground">
                        Quotation, estimasi harga, proposal, daftar kebutuhan,
                        atau screenshot harga.
                    </p>
                    <Input
                        type="file"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.doc,.docx"
                        onChange={(e) =>
                            f.setData(
                                'attachments',
                                Array.from(e.target.files ?? []),
                            )
                        }
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
                    variant="outline"
                    disabled={step === 1}
                    onClick={() => setStep((x) => x - 1)}
                >
                    <ChevronLeft />
                    Kembali
                </Button>
                {step < 4 ? (
                    <Button onClick={() => setStep((x) => x + 1)}>
                        Lanjut
                        <ChevronRight />
                    </Button>
                ) : (
                    <Button disabled={f.processing} onClick={save}>
                        Simpan Draft
                    </Button>
                )}
            </div>
        </div>
    );
}
