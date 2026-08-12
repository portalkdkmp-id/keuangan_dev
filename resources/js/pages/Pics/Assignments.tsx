import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { BackButton } from '@/components/back-button';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

export default function Assignments({
    pic,
    cooperatives,
    assignedIds,
    filters,
}: any) {
    const visibleIds = useMemo(
        () => cooperatives.data.map((item: any) => item.id),
        [cooperatives.data],
    );
    const form = useForm({
        cooperative_ids: assignedIds as string[],
        visible_cooperative_ids: visibleIds,
    });
    const [assignment, setAssignment] = useState(filters.assignment || 'all');
    const allChecked =
        visibleIds.length > 0 &&
        visibleIds.every((id: string) =>
            form.data.cooperative_ids.includes(id),
        );
    const toggleAll = (checked: boolean) =>
        form.setData('cooperative_ids', checked ? visibleIds : []);
    const toggle = (id: string, checked: boolean) =>
        form.setData(
            'cooperative_ids',
            checked
                ? [...form.data.cooperative_ids, id]
                : form.data.cooperative_ids.filter((item) => item !== id),
        );

    return (
        <div className="space-y-5 p-4 sm:p-6">
            <Head title={`Assign Koperasi - ${pic.name}`} />
            <BackButton fallback="/pics" />
            <header>
                <h1 className="text-2xl font-semibold">Assign Koperasi</h1>
                <p className="text-sm text-muted-foreground">
                    {pic.name} · Wilayah {pic.city?.name}
                </p>
            </header>
            <form
                className="flex flex-col gap-2 sm:flex-row"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        `/pics/${pic.id}/assignments`,
                        {
                            search: new FormData(event.currentTarget).get(
                                'search',
                            ),
                            assignment: assignment === 'all' ? '' : assignment,
                        },
                        { preserveState: true },
                    );
                }}
            >
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Cari nama atau NIK koperasi"
                />
                <Select value={assignment} onValueChange={setAssignment}>
                    <SelectTrigger className="sm:w-52">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua assignment</SelectItem>
                        <SelectItem value="assigned">Sudah diassign</SelectItem>
                        <SelectItem value="unassigned">
                            Belum diassign
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button>Terapkan Filter</Button>
            </form>
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put(`/pics/${pic.id}/assignments`, {
                        preserveScroll: true,
                    });
                }}
            >
                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox
                                        checked={allChecked}
                                        onCheckedChange={(value) =>
                                            toggleAll(value === true)
                                        }
                                        aria-label="Pilih semua koperasi pada halaman ini"
                                    />
                                </TableHead>
                                <TableHead>Koperasi</TableHead>
                                <TableHead>NIK</TableHead>
                                <TableHead>Kecamatan</TableHead>
                                <TableHead>Desa</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {cooperatives.data.map((cooperative: any) => (
                                <TableRow key={cooperative.id}>
                                    <TableCell>
                                        <Checkbox
                                            checked={form.data.cooperative_ids.includes(
                                                cooperative.id,
                                            )}
                                            onCheckedChange={(value) =>
                                                toggle(
                                                    cooperative.id,
                                                    value === true,
                                                )
                                            }
                                            aria-label={`Pilih ${cooperative.name}`}
                                        />
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {cooperative.name}
                                    </TableCell>
                                    <TableCell>{cooperative.nik}</TableCell>
                                    <TableCell>
                                        {cooperative.district?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {cooperative.village?.name ?? '-'}
                                    </TableCell>
                                </TableRow>
                            ))}
                            {cooperatives.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Koperasi tidak ditemukan pada wilayah
                                        ini.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                {form.errors.cooperative_ids && (
                    <p className="text-sm text-destructive">
                        {form.errors.cooperative_ids}
                    </p>
                )}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <SimplePagination meta={cooperatives} />
                    <Button
                        disabled={form.processing || visibleIds.length === 0}
                    >
                        {form.processing
                            ? 'Menyimpan...'
                            : 'Simpan Assignment Halaman Ini'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
