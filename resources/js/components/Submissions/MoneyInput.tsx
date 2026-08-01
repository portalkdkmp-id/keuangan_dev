import type React from 'react';
import { Input } from '@/components/ui/input';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

type MoneyInputProps = Omit<React.ComponentProps<typeof Input>, 'value' | 'onChange'> & {
    value?: string | number | null;
    onChange: (value: string) => void;
};

function normalizeMoneyValue(value?: string | number | null): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const rawValue = String(value);
    const wholeValue = /^\d+\.\d+$/.test(rawValue) ? rawValue.split('.')[0] : rawValue;

    return wholeValue.replace(/\D/g, '');
}

export function MoneyInput({ value, onChange, placeholder = 'Rp 0', ...props }: MoneyInputProps) {
    const numericValue = normalizeMoneyValue(value);

    return (
        <Input
            {...props}
            inputMode="numeric"
            placeholder={placeholder}
            value={numericValue ? rupiah(Number(numericValue)) : ''}
            onChange={(event) => onChange(event.target.value.replace(/\D/g, ''))}
        />
    );
}
