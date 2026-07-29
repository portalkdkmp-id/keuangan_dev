export function rupiah(value: string | number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}

export function SubmissionSummary({ total }: { total: string | number }) {
    return <div className="rounded-md border p-4 text-right text-xl font-semibold">{rupiah(total)}</div>;
}
