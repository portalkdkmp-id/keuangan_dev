import { Head, Link, router } from '@inertiajs/react';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { CooperativeCombobox } from '@/components/cooperative-combobox';

const labels: Record<string, string> = {
    total_disbursed: 'Total Dicairkan',
    total_distributed: 'Total Didistribusikan',
    total_confirmed: 'Total Dikonfirmasi',
    total_realized: 'Total Direalisasikan',
    total_remaining: 'Total Sisa Dana',
    total_additional: 'Total Kekurangan Dana',
    undistributed_amount: 'Belum Didistribusikan',
    unaccounted_amount: 'Belum Dipertanggungjawabkan',
    waiting_distribution: 'Menunggu Distribusi',
    waiting_confirmation: 'Menunggu Konfirmasi',
    waiting_accountability: 'Menunggu Laporan',
    accountability_submitted: 'Laporan Submitted',
    accountability_approved: 'Laporan Approved',
    distribution_completion_rate: 'Penyelesaian Distribusi',
    accountability_submission_rate: 'Pengiriman Laporan',
    accountability_approval_rate: 'Approval Laporan',
    average_distribution_hours: 'Rata-rata Waktu Distribusi',
    average_accountability_hours: 'Rata-rata Waktu Laporan',
};
const recipientTypes = [
    { id: 'finance_staff', name: 'Finance Staff' },
    { id: 'pic_kdkmp', name: 'PIC KDKMP' },
    { id: 'cooperative', name: 'Koperasi' },
    { id: 'other', name: 'Lainnya' },
];
const distributionStatuses = [
    'pending',
    'partially_distributed',
    'fully_distributed',
    'accountability_pending',
    'accountability_submitted',
    'under_verification',
    'closed',
].map((id) => ({ id, name: id }));
const accountabilityStatuses = [
    'draft',
    'submitted',
    'finance_review',
    'revision_requested',
    'finance_verified',
    'closed',
].map((id) => ({ id, name: id }));

export default function Index({
    stats,
    latestDisbursements,
    filters,
    provinces,
    cities,
    cooperatives,
    pics,
    financeStaff,
    financeApprovers,
    directors,
    categories,
    submissionTypes,
}: any) {
    const apply = (key: string, value: string) =>
        router.get(
            '/monitoring/funds',
            { ...filters, [key]: value || undefined },
            { preserveState: true, replace: true },
        );
    const visibleCities = filters.province_id
        ? cities.filter((city: any) => city.province_id === filters.province_id)
        : cities;
    const statValue = (key: string, value: any) =>
        key.includes('rate')
            ? `${value}%`
            : key.includes('hours')
              ? `${value} jam`
              : key.includes('amount') || key.startsWith('total_')
                ? rupiah(value)
                : value;

    return (
        <div className="space-y-5 p-4">
            <Head title="Monitoring Dana" />
            <div>
                <h1 className="text-2xl font-semibold">
                    Monitoring Perjalanan Dana
                </h1>
                <p className="text-sm text-muted-foreground">
                    Pencairan, distribusi, penerimaan, dan pertanggungjawaban
                    dalam satu tampilan.
                </p>
            </div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <Input
                    type="date"
                    value={filters.date_from ?? ''}
                    onChange={(event) => apply('date_from', event.target.value)}
                />
                <Input
                    type="date"
                    value={filters.date_to ?? ''}
                    onChange={(event) => apply('date_to', event.target.value)}
                />
                <Filter
                    value={filters.province_id}
                    placeholder="Semua provinsi"
                    items={provinces}
                    onChange={(value: string) => apply('province_id', value)}
                />
                <Filter
                    value={filters.city_id}
                    placeholder="Semua kota/kabupaten"
                    items={visibleCities}
                    onChange={(value: string) => apply('city_id', value)}
                />
                <CooperativeCombobox
                    value={filters.cooperative_id}
                    placeholder="Cari koperasi"
                    cooperatives={cooperatives}
                    onValueChange={(value: string) =>
                        apply('cooperative_id', value)
                    }
                />
                <Filter
                    value={filters.pic_id}
                    placeholder="Semua PIC"
                    items={pics}
                    onChange={(value: string) => apply('pic_id', value)}
                />
                <Filter
                    value={filters.finance_staff_id}
                    placeholder="Semua Finance Staff"
                    items={financeStaff}
                    onChange={(value: string) =>
                        apply('finance_staff_id', value)
                    }
                />
                <Filter
                    value={filters.finance_approver_id}
                    placeholder="Semua Finance Approver"
                    items={financeApprovers}
                    onChange={(value: string) =>
                        apply('finance_approver_id', value)
                    }
                />
                <Filter
                    value={filters.director_id}
                    placeholder="Semua Director"
                    items={directors}
                    onChange={(value: string) => apply('director_id', value)}
                />
                <Filter
                    value={filters.category_id}
                    placeholder="Semua kategori"
                    items={categories}
                    onChange={(value: string) => apply('category_id', value)}
                />
                <Filter
                    value={filters.submission_type_id}
                    placeholder="Semua jenis pengajuan"
                    items={submissionTypes}
                    onChange={(value: string) =>
                        apply('submission_type_id', value)
                    }
                />
                <Filter
                    value={filters.recipient_type}
                    placeholder="Semua penerima"
                    items={recipientTypes}
                    onChange={(value: string) => apply('recipient_type', value)}
                />
                <Filter
                    value={filters.distribution_status}
                    placeholder="Semua status distribusi"
                    items={distributionStatuses}
                    onChange={(value: string) =>
                        apply('distribution_status', value)
                    }
                />
                <Filter
                    value={filters.accountability_status}
                    placeholder="Semua status laporan"
                    items={accountabilityStatuses}
                    onChange={(value: string) =>
                        apply('accountability_status', value)
                    }
                />
            </div>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {Object.entries(stats).map(([key, value]) => (
                    <Card key={key}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                {labels[key] ?? key}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-xl font-semibold">
                            {statValue(key, value)}
                        </CardContent>
                    </Card>
                ))}
            </div>
            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Pencairan</TableHead>
                            <TableHead>Pengajuan</TableHead>
                            <TableHead>Penerima</TableHead>
                            <TableHead>Nominal</TableHead>
                            <TableHead>Status Dana</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {latestDisbursements.data.map((item: any) => (
                            <TableRow key={item.id}>
                                <TableCell>
                                    {item.disbursement_number}
                                </TableCell>
                                <TableCell>
                                    {item.submission?.submission_number}
                                </TableCell>
                                <TableCell>
                                    {item.recipient_name_snapshot}
                                </TableCell>
                                <TableCell>{rupiah(item.amount)}</TableCell>
                                <TableCell>
                                    {item.distribution_status}
                                </TableCell>
                                <TableCell>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={`/monitoring/funds/${item.financial_submission_id}`}
                                        >
                                            Timeline
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}

function Filter({ value, placeholder, items, onChange }: any) {
    return (
        <Select
            value={value ?? 'all'}
            onValueChange={(next) => onChange(next === 'all' ? '' : next)}
        >
            <SelectTrigger>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">{placeholder}</SelectItem>
                {items.map((item: any) => (
                    <SelectItem key={item.id} value={item.id}>
                        {item.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
