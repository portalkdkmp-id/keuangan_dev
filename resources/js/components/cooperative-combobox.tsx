import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from '@/components/ui/combobox';

type Cooperative = {
    id: string;
    name: string;
};

type Props = {
    cooperatives: Cooperative[];
    value?: string;
    onValueChange: (value: string) => void;
    allowInternal?: boolean;
    disabled?: boolean;
    name?: string;
    placeholder?: string;
};

const internal = { id: '__internal__', name: 'Internal / Tanpa Koperasi' };

export function CooperativeCombobox({
    cooperatives,
    value = '',
    onValueChange,
    allowInternal = false,
    disabled = false,
    name,
    placeholder = 'Cari koperasi',
}: Props) {
    const items = allowInternal ? [internal, ...cooperatives] : cooperatives;
    const selectedValue = items.find((item) => item.id === value) ?? null;

    return (
        <>
            {name && <input type="hidden" name={name} value={value} />}
            <Combobox
                items={items}
                value={selectedValue}
                onValueChange={(nextValue) =>
                    onValueChange(
                        nextValue?.id === internal.id
                            ? ''
                            : (nextValue?.id ?? ''),
                    )
                }
                itemToStringLabel={(item) => item.name}
                itemToStringValue={(item) => item.id}
                isItemEqualToValue={(item, selected) => item.id === selected.id}
                disabled={disabled}
            >
                <ComboboxInput
                    className="w-full"
                    placeholder={placeholder}
                    showClear
                    aria-label={placeholder}
                />
                <ComboboxContent>
                    <ComboboxEmpty>Koperasi tidak ditemukan.</ComboboxEmpty>
                    <ComboboxList>
                        {(item: Cooperative) => (
                            <ComboboxItem key={item.id} value={item}>
                                {item.name}
                            </ComboboxItem>
                        )}
                    </ComboboxList>
                </ComboboxContent>
            </Combobox>
        </>
    );
}
