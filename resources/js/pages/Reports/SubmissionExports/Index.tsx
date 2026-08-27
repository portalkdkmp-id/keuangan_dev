import { Head, router } from '@inertiajs/react';
import { Download, FileArchive, Filter, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { CooperativeCombobox } from '@/components/cooperative-combobox';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SimplePagination } from '@/components/simple-pagination';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

const fields = [
    'search',
    'status',
    'cooperative_id',
    'pic_id',
    'created_from',
    'created_to',
    'status_updated_from',
    'status_updated_to',
] as const;

export default function SubmissionExportIndex({
    submissions,
    filters,
    statuses,
    cooperatives,
    pics,
    isPic,
    canExportFinancialReports,
}: any) {
    const [cooperativeId, setCooperativeId] = useState(
        filters.cooperative_id ?? '',
    );
    const downloadQuery = new URLSearchParams(
        Object.fromEntries(
            fields
                .map((key) => [key, filters[key]])
                .filter(([, value]) => Boolean(value)),
        ),
    ).toString();

    return (
        <div className="space-y-5 p-4">
            <Head title="Export Laporan" />
            <div>
                <h1 className="text-2xl font-semibold">Export Laporan</h1>
                <p className="text-sm text-muted-foreground">
                    {isPic
                        ? 'Tinjau dan export seluruh pengajuan yang Anda buat.'
                        : 'Tinjau dan export seluruh pengajuan berdasarkan filter laporan.'}
                </p>
            </div>

            <form
                className="grid gap-4 border-y py-4 sm:grid-cols-2 lg:grid-cols-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    const values = Object.fromEntries(
                        new FormData(event.currentTarget),
                    );
                    Object.keys(values).forEach((key) => {
                        if (!values[key] || values[key] === 'all')
                            delete values[key];
                    });
                    router.get('/export-laporan', values, {
                        preserveState: true,
                        replace: true,
                    });
                }}
            >
                <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="search">Pencarian</Label>
                    <Input
                        id="search"
                        name="search"
                        defaultValue={filters.search ?? ''}
                        placeholder="Cari judul atau nama item pengajuan"
                    />
                </div>
                <FilterSelect
                    label="Status akhir"
                    name="status"
                    value={filters.status}
                    options={statuses}
                />
                <div className="space-y-2">
                    <Label>Koperasi</Label>
                    <CooperativeCombobox
                        name="cooperative_id"
                        cooperatives={cooperatives}
                        value={cooperativeId}
                        onValueChange={setCooperativeId}
                        placeholder="Cari koperasi"
                    />
                </div>
                {!isPic && (
                    <FilterSelect
                        label="PIC"
                        name="pic_id"
                        value={filters.pic_id}
                        options={pics.map((item: any) => ({
                            value: item.id,
                            label: item.name,
                        }))}
                    />
                )}
                <DateField
                    label="Dibuat mulai"
                    name="created_from"
                    value={filters.created_from}
                />
                <DateField
                    label="Dibuat sampai"
                    name="created_to"
                    value={filters.created_to}
                />
                <DateField
                    label="Update status mulai"
                    name="status_updated_from"
                    value={filters.status_updated_from}
                />
                <DateField
                    label="Update status sampai"
                    name="status_updated_to"
                    value={filters.status_updated_to}
                />
                <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
                    <Button type="submit" className="flex-1">
                        <Filter className="size-4" /> Terapkan
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        title="Reset filter"
                        onClick={() => router.get('/export-laporan')}
                    >
                        <RotateCcw className="size-4" />
                    </Button>
                </div>
            </form>

            <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <p className="text-sm text-muted-foreground">
                    {submissions.total} pengajuan ditemukan
                </p>
                <div className="flex flex-wrap gap-2">
                    <ExportButton
                        href="/export-laporan/download"
                        query={downloadQuery}
                        label="Export Pengajuan"
                    />
                    {canExportFinancialReports && (
                        <>
                            <ExportButton
                                href="/export-laporan/download/lpj"
                                query={downloadQuery}
                                label="Export Penanggungjawaban"
                            />
                            <ExportButton
                                href="/export-laporan/download/aging"
                                query={downloadQuery}
                                label="Aging-Outstanding"
                            />
                            <Button asChild disabled={submissions.total === 0}>
                                <a
                                    href={`/export-laporan/download/complete${downloadQuery ? `?${downloadQuery}` : ''}`}
                                >
                                    <FileArchive className="size-4" /> Export
                                    Laporan Lengkap
                                </a>
                            </Button>
                        </>
                    )}
                </div>
            </div>

            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nomor</TableHead>
                            <TableHead>Judul</TableHead>
                            {!isPic && <TableHead>PIC</TableHead>}
                            <TableHead>Koperasi</TableHead>
                            <TableHead>Dibuat</TableHead>
                            <TableHead>Update Status</TableHead>
                            <TableHead>Nominal</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {submissions.data.map((submission: any) => (
                            <TableRow key={submission.id}>
                                <TableCell className="font-medium">
                                    {submission.submission_number}
                                </TableCell>
                                <TableCell>{submission.title}</TableCell>
                                {!isPic && (
                                    <TableCell>
                                        {submission.submitter?.name ?? '-'}
                                    </TableCell>
                                )}
                                <TableCell>
                                    {submission.cooperative?.name ?? 'Internal'}
                                </TableCell>
                                <TableCell>
                                    {formatDate(submission.created_at)}
                                </TableCell>
                                <TableCell>
                                    {formatDate(
                                        submission.status_histories_max_created_at,
                                    )}
                                </TableCell>
                                <TableCell>
                                    {rupiah(submission.total_amount)}
                                </TableCell>
                                <TableCell>
                                    <SubmissionStatusBadge
                                        status={submission.status}
                                    />
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        asChild
                                        title="Export pengajuan"
                                    >
                                        <a
                                            href={`/export-laporan/${submission.id}/download`}
                                        >
                                            <Download className="size-4" />
                                        </a>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {submissions.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={isPic ? 8 : 9}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    Tidak ada pengajuan sesuai filter.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <SimplePagination meta={submissions} />
        </div>
    );
}

function ExportButton({ href, query, label }: any) {
    return (
        <Button asChild variant="outline">
            <a href={`${href}${query ? `?${query}` : ''}`}>
                <Download className="size-4" /> {label}
            </a>
        </Button>
    );
}

function FilterSelect({ label, name, value, options }: any) {
    return (
        <div className="space-y-2">
            <Label htmlFor={name}>{label}</Label>
            <Select name={name} defaultValue={value || 'all'}>
                <SelectTrigger id={name} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua</SelectItem>
                    {options.map((option: any) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function DateField({ label, name, value }: any) {
    return (
        <div className="space-y-2">
            <Label htmlFor={name}>{label}</Label>
            <Input
                id={name}
                name={name}
                type="date"
                defaultValue={value ?? ''}
            />
        </div>
    );
}
