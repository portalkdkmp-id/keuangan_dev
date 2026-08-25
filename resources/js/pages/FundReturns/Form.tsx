import { Head, router, useForm } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
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
export default function Form({
    report,
    fundReturn,
    bankAccounts,
    companyAccounts,
}: any) {
    const f = useForm<any>({
        source_user_bank_account_id:
            fundReturn?.source_user_bank_account_id ?? '',
        destination_company_bank_account_id:
            fundReturn?.destination_company_bank_account_id ?? '',
        transfer_date: fundReturn?.transfer_date?.slice(0, 10) ?? '',
        transferred_at: fundReturn?.transferred_at?.slice(0, 16) ?? '',
        payment_method: fundReturn?.payment_method ?? 'bank_transfer',
        transaction_reference: fundReturn?.transaction_reference ?? '',
        notes: fundReturn?.notes ?? '',
        proofs: [] as File[],
    });
    const save = () =>
        fundReturn
            ? router.post(
                  `/fund-returns/${fundReturn.id}`,
                  { ...f.data, _method: 'put' },
                  { forceFormData: true },
              )
            : f.post(`/fund-returns/${report.id}`, { forceFormData: true });
    return (
        <div className="mx-auto max-w-2xl space-y-5 p-4">
            <Head title="Pengembalian Sisa Dana" />
            <BackButton fallback="/fund-returns" />
            <div>
                <h1 className="text-2xl font-semibold">
                    Pengembalian Sisa Dana
                </h1>
                <p className="text-sm text-muted-foreground">
                    {report.submission.submission_number}
                </p>
            </div>
            <div className="rounded-md border p-4">
                <div className="text-sm text-muted-foreground">
                    Nominal yang harus dikembalikan
                </div>
                <div className="text-2xl font-semibold">
                    {rupiah(report.remaining_amount)}
                </div>
            </div>
            <div>
                <Label>Metode pembayaran</Label>
                <Select
                    value={f.data.payment_method}
                    onValueChange={(v) => f.setData('payment_method', v)}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="bank_transfer">
                            Transfer bank
                        </SelectItem>
                        <SelectItem value="cash">Tunai</SelectItem>
                        <SelectItem value="other">Lainnya</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            {f.data.payment_method === 'bank_transfer' && (
                <div>
                    <Label>Rekening sumber</Label>
                    <Select
                        value={f.data.source_user_bank_account_id}
                        onValueChange={(v) =>
                            f.setData('source_user_bank_account_id', v)
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
            )}
            <div>
                <Label>Rekening perusahaan tujuan</Label>
                <Select
                    value={f.data.destination_company_bank_account_id}
                    onValueChange={(v) =>
                        f.setData('destination_company_bank_account_id', v)
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Pilih rekening perusahaan" />
                    </SelectTrigger>
                    <SelectContent>
                        {companyAccounts.map((x: any) => (
                            <SelectItem key={x.id} value={x.id}>
                                {x.bank_name} - {x.account_number} (
                                {x.account_holder_name})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <Label>Tanggal transfer</Label>
                    <Input
                        type="date"
                        value={f.data.transfer_date}
                        onChange={(e) =>
                            f.setData('transfer_date', e.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Waktu transfer</Label>
                    <Input
                        type="datetime-local"
                        value={f.data.transferred_at}
                        onChange={(e) =>
                            f.setData('transferred_at', e.target.value)
                        }
                    />
                </div>
            </div>
            <div>
                <Label>Nomor referensi</Label>
                <Input
                    value={f.data.transaction_reference}
                    onChange={(e) =>
                        f.setData('transaction_reference', e.target.value)
                    }
                />
            </div>
            <div>
                <Label>Catatan</Label>
                <Textarea
                    value={f.data.notes}
                    onChange={(e) => f.setData('notes', e.target.value)}
                />
            </div>
            <div>
                <MultipleFileInput
                    label={
                        f.data.payment_method === 'cash'
                            ? 'Bukti serah terima'
                            : 'Bukti transfer'
                    }
                    files={f.data.proofs}
                    onFiles={(files) => f.setData('proofs', files)}
                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                    description="Pilih beberapa bukti pengembalian sekaligus jika diperlukan."
                />
            </div>
            <Button disabled={f.processing} onClick={save}>
                {f.processing ? 'Menyimpan...' : 'Simpan Draft'}
            </Button>
        </div>
    );
}
