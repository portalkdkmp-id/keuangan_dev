import { useMemo, useState } from 'react';
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
import { rupiah } from './SubmissionSummary';

export function SubmissionForm({
    form,
    cooperatives,
    requestCategories,
    requestTypes,
    bankAccounts,
    onSubmit,
    submitLabel = 'Simpan Draft',
    canSubmitInternal = false,
}: any) {
    const [step, setStep] = useState(0);
    const selectedCategory = requestCategories.find(
        (category: any) =>
            category.id === form.data.submission_request_category_id,
    );
    const selectedType = requestTypes.find(
        (type: any) => type.id === form.data.submission_request_type_id,
    );
    const selectedAccount = bankAccounts.find(
        (account: any) => account.id === form.data.recipient_bank_account_id,
    );
    const amount = useMemo(
        () => Number(form.data.amount || 0),
        [form.data.amount],
    );
    const canContinue = [
        Boolean(form.data.submission_request_category_id),
        Boolean(form.data.title) &&
            (canSubmitInternal || Boolean(form.data.cooperative_id)) &&
            Boolean(form.data.submission_request_type_id),
        Boolean(form.data.amount) &&
            Boolean(form.data.needed_date) &&
            Boolean(form.data.recipient_bank_account_id),
    ][step];
    const errors = Object.values(form.errors ?? {}) as string[];

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit();
            }}
            className="space-y-4"
        >
            {errors.length > 0 && (
                <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                    {errors[0]}
                </div>
            )}
            {bankAccounts.length === 0 && (
                <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                    Tambahkan rekening terlebih dahulu sebelum membuat pengajuan
                    dana.
                </div>
            )}
            {requestCategories.length === 0 && (
                <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                    Master kategori pengajuan belum tersedia.
                </div>
            )}
            {requestTypes.length === 0 && (
                <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                    Master jenis pengajuan belum tersedia.
                </div>
            )}
            <div className="grid gap-3 md:grid-cols-3">
                {requestCategories.map((category: any) => (
                    <button
                        key={category.id}
                        type="button"
                        onClick={() => {
                            form.setData(
                                'submission_request_category_id',
                                category.id,
                            );
                            setStep(1);
                        }}
                        className={`min-h-24 rounded-md border p-4 text-left text-lg font-semibold transition ${form.data.submission_request_category_id === category.id ? 'border-primary bg-primary/10' : 'hover:bg-muted/60'}`}
                    >
                        {category.name}
                    </button>
                ))}
            </div>

            <div className="rounded-md border p-4">
                <div className="mb-4 flex items-center justify-between text-sm">
                    <span className="font-medium">
                        Langkah {step + 1} dari 3
                    </span>
                    <span className="text-muted-foreground">
                        {selectedCategory?.name ?? 'Pilih kategori'}
                    </span>
                </div>

                {step === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Pilih salah satu kategori pengajuan di atas.
                    </p>
                )}

                {step === 1 && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <Label>Title pengajuan</Label>
                            <Input
                                value={form.data.title ?? ''}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                placeholder="Contoh: Pengajuan ATK bulan Juli"
                            />
                        </div>
                        <div>
                            <Label>
                                Koperasi{canSubmitInternal ? ' (opsional)' : ''}
                            </Label>
                            <Select
                                value={
                                    form.data.cooperative_id ||
                                    (canSubmitInternal
                                        ? '__internal__'
                                        : undefined)
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'cooperative_id',
                                        value === '__internal__' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Pilih koperasi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {canSubmitInternal && (
                                        <SelectItem value="__internal__">
                                            Internal / Tanpa Koperasi
                                        </SelectItem>
                                    )}
                                    {cooperatives.map((cooperative: any) => (
                                        <SelectItem
                                            key={cooperative.id}
                                            value={cooperative.id}
                                        >
                                            {cooperative.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Jenis pengajuan</Label>
                            <Select
                                value={
                                    form.data.submission_request_type_id ||
                                    undefined
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'submission_request_type_id',
                                        value,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Pilih jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    {requestTypes.map((type: any) => (
                                        <SelectItem
                                            key={type.id}
                                            value={type.id}
                                        >
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                )}

                {step === 2 && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Nominal pengajuan</Label>
                            <Input
                                type="number"
                                inputMode="decimal"
                                value={form.data.amount}
                                onChange={(e) =>
                                    form.setData('amount', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label>Tanggal dibutuhkan</Label>
                            <Input
                                type="date"
                                value={form.data.needed_date ?? ''}
                                onChange={(e) =>
                                    form.setData('needed_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Label>Rekening penerima</Label>
                            <Select
                                value={
                                    form.data.recipient_bank_account_id ||
                                    undefined
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'recipient_bank_account_id',
                                        value,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Pilih rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    {bankAccounts.map((account: any) => (
                                        <SelectItem
                                            key={account.id}
                                            value={account.id}
                                        >
                                            {account.bank_name} -{' '}
                                            {account.account_holder_name} -{' '}
                                            {account.account_number}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="md:col-span-2">
                            <Label>Catatan</Label>
                            <textarea
                                className="min-h-28 w-full rounded-md border bg-background p-3 text-sm"
                                value={form.data.notes ?? ''}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                            />
                        </div>
                        <div className="rounded-md bg-muted p-3 text-sm md:col-span-2">
                            <div>
                                {selectedCategory?.name} · {selectedType?.name}
                            </div>
                            <div>{rupiah(amount)}</div>
                            <div>
                                {selectedAccount
                                    ? `${selectedAccount.bank_name} - ${selectedAccount.account_holder_name}`
                                    : '-'}
                            </div>
                        </div>
                    </div>
                )}

                <div className="mt-4 flex justify-between gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={step === 0}
                        onClick={() =>
                            setStep((current) => Math.max(current - 1, 0))
                        }
                    >
                        Kembali
                    </Button>
                    {step < 2 ? (
                        <Button
                            type="button"
                            disabled={!canContinue}
                            onClick={() =>
                                setStep((current) => Math.min(current + 1, 2))
                            }
                        >
                            Lanjut
                        </Button>
                    ) : (
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                !canContinue ||
                                bankAccounts.length === 0 ||
                                requestCategories.length === 0 ||
                                requestTypes.length === 0
                            }
                        >
                            {submitLabel}
                        </Button>
                    )}
                </div>
            </div>
        </form>
    );
}
