import { Head, useForm } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { CooperativeCombobox } from '@/components/cooperative-combobox';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export function MasterBankAccountForm({
    title,
    baseUrl,
    account,
    cooperatives = [],
    company = false,
}: any) {
    const form = useForm({
        cooperative_id: account?.cooperative_id ?? '',
        bank_name: account?.bank_name ?? '',
        account_number: account?.account_number ?? '',
        account_holder_name: account?.account_holder_name ?? '',
        description: account?.description ?? '',
        is_active: account?.is_active ?? true,
        is_primary: account?.is_primary ?? false,
    });
    const submit = () =>
        account ? form.put(`${baseUrl}/${account.id}`) : form.post(baseUrl);
    return (
        <div className="mx-auto max-w-2xl space-y-4 p-4">
            <Head title={title} />
            <BackButton fallback={baseUrl} />
            <h1 className="text-2xl font-semibold">{title}</h1>
            <form
                className="space-y-4 rounded-md border p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    submit();
                }}
            >
                {!company && (
                    <div>
                        <Label>Koperasi</Label>
                        <CooperativeCombobox
                            cooperatives={cooperatives}
                            value={form.data.cooperative_id}
                            onValueChange={(value) =>
                                form.setData('cooperative_id', value)
                            }
                        />
                    </div>
                )}
                <div>
                    <Label>Nama bank</Label>
                    <Input
                        value={form.data.bank_name}
                        onChange={(e) =>
                            form.setData('bank_name', e.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Nomor rekening</Label>
                    <Input
                        value={form.data.account_number}
                        onChange={(e) =>
                            form.setData('account_number', e.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Nama pada rekening</Label>
                    <Input
                        value={form.data.account_holder_name}
                        onChange={(e) =>
                            form.setData('account_holder_name', e.target.value)
                        }
                    />
                </div>
                {company && (
                    <div>
                        <Label>Deskripsi</Label>
                        <Textarea
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </div>
                )}
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_active}
                        onCheckedChange={(value) =>
                            form.setData('is_active', value === true)
                        }
                    />
                    Aktif
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_primary}
                        onCheckedChange={(value) =>
                            form.setData('is_primary', value === true)
                        }
                    />
                    Rekening utama
                </label>
                <Button disabled={form.processing}>
                    {form.processing ? 'Menyimpan...' : 'Simpan'}
                </Button>
            </form>
        </div>
    );
}
