import { Plus, Trash2 } from 'lucide-react';
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

export const emptySubmissionItem = () => ({
    name: '',
    request_type_id: '',
    other_type_name: '',
    amount: '',
});

export function SubmissionItemsEditor({ form, requestTypes }: any) {
    const items = form.data.items ?? [];
    const total = items.reduce(
        (sum: number, item: any) => sum + Number(item.amount || 0),
        0,
    );
    const updateItem = (index: number, field: string, value: string) =>
        form.setData(
            'items',
            items.map((item: any, itemIndex: number) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h3 className="font-semibold">Item Pengajuan</h3>
                    <p className="text-xs text-muted-foreground">
                        Masukkan satu atau beberapa kebutuhan dalam pengajuan
                        ini.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                        form.setData('items', [...items, emptySubmissionItem()])
                    }
                >
                    <Plus />
                    Tambah Item
                </Button>
            </div>
            {items.map((item: any, index: number) => {
                const selectedType = requestTypes.find(
                    (type: any) => type.id === item.request_type_id,
                );
                const isOther = ['lainnya', 'other'].includes(
                    selectedType?.slug,
                );
                return (
                    <div
                        key={item.id ?? index}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium">
                                Item {index + 1}
                            </span>
                            {items.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    title="Hapus item"
                                    onClick={() =>
                                        form.setData(
                                            'items',
                                            items.filter(
                                                (_: any, itemIndex: number) =>
                                                    itemIndex !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            )}
                        </div>
                        <div>
                            <Label>Nama item</Label>
                            <Input
                                value={item.name}
                                placeholder="Contoh: Sewa kendaraan operasional"
                                onChange={(event) =>
                                    updateItem(
                                        index,
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Jenis item pengajuan</Label>
                                <Select
                                    value={item.request_type_id || undefined}
                                    onValueChange={(value) => {
                                        const nextType = requestTypes.find(
                                            (type: any) => type.id === value,
                                        );
                                        form.setData(
                                            'items',
                                            items.map(
                                                (
                                                    current: any,
                                                    itemIndex: number,
                                                ) =>
                                                    itemIndex === index
                                                        ? {
                                                              ...current,
                                                              request_type_id:
                                                                  value,
                                                              other_type_name: [
                                                                  'lainnya',
                                                                  'other',
                                                              ].includes(
                                                                  nextType?.slug,
                                                              )
                                                                  ? current.other_type_name
                                                                  : '',
                                                          }
                                                        : current,
                                            ),
                                        );
                                    }}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Pilih jenis item" />
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
                            <div>
                                <Label>Nominal</Label>
                                <MoneyInput
                                    value={item.amount}
                                    onChange={(value) =>
                                        updateItem(index, 'amount', value)
                                    }
                                />
                            </div>
                        </div>
                        {isOther && (
                            <div>
                                <Label>Jenis item lainnya</Label>
                                <Input
                                    value={item.other_type_name}
                                    placeholder="Tuliskan jenis item"
                                    onChange={(event) =>
                                        updateItem(
                                            index,
                                            'other_type_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                    </div>
                );
            })}
            <div className="flex items-center justify-between border-t pt-3 text-sm">
                <span className="font-medium">Total Pengajuan</span>
                <strong className="text-lg">{rupiah(total)}</strong>
            </div>
        </div>
    );
}
