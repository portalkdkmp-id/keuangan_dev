import { useState } from 'react';
import { CooperativeCombobox } from '@/components/cooperative-combobox';
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
import { SubmissionItemsEditor } from './SubmissionItemsEditor';

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
    const availableRequestTypes = requestTypes.filter(
        (type: any) =>
            !type.submission_request_category_id ||
            type.submission_request_category_id ===
                form.data.submission_request_category_id,
    );
    const selectedAccount = bankAccounts.find(
        (account: any) => account.id === form.data.recipient_bank_account_id,
    );
    const itemsComplete =
        form.data.items?.length > 0 &&
        form.data.items.every((item: any) => {
            const type = requestTypes.find(
                (requestType: any) => requestType.id === item.request_type_id,
            );
            return (
                Boolean(item.name) &&
                Boolean(item.request_type_id) &&
                Number(item.amount) > 0 &&
                (!['lainnya', 'other'].includes(type?.slug) ||
                    Boolean(item.other_type_name))
            );
        });
    const canContinue = [
        Boolean(form.data.submission_request_category_id),
        Boolean(form.data.title) &&
            (canSubmitInternal || Boolean(form.data.cooperative_id)),
        itemsComplete &&
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
                            form.setData((data: any) => ({
                                ...data,
                                submission_request_category_id: category.id,
                                items: data.items.map((item: any) => {
                                    const type = requestTypes.find(
                                        (candidate: any) =>
                                            candidate.id ===
                                            item.request_type_id,
                                    );
                                    return type?.submission_request_category_id &&
                                        type.submission_request_category_id !==
                                            category.id
                                        ? {
                                              ...item,
                                              request_type_id: '',
                                              other_type_name: '',
                                          }
                                        : item;
                                }),
                            }));
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
                                id="submission-form-is-urgent"
                                checked={form.data.is_urgent ?? false}
                                onCheckedChange={(value) =>
                                    form.setData('is_urgent', value === true)
                                }
                            />
                            <Label htmlFor="submission-form-is-urgent">
                                Pengajuan Urgent
                            </Label>
                        </div>
                    </div>
                )}

                {step === 2 && (
                    <div className="space-y-4">
                        <SubmissionItemsEditor
                            form={form}
                            requestTypes={availableRequestTypes}
                        />
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Tanggal dibutuhkan</Label>
                                <Input
                                    type="date"
                                    value={form.data.needed_date ?? ''}
                                    onChange={(e) =>
                                        form.setData(
                                            'needed_date',
                                            e.target.value,
                                        )
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
