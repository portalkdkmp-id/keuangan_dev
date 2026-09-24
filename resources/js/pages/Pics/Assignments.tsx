import { Head, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import { BackButton } from '@/components/back-button';
import { AssignmentFilters } from '@/components/Pics/AssignmentFilters';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    regions,
}: any) {
    const visibleIds = useMemo(
        () => cooperatives.data.map((item: any) => item.id),
        [cooperatives.data],
    );
    const form = useForm({
        cooperative_ids: assignedIds as string[],
        visible_cooperative_ids: visibleIds,
    });
    useEffect(() => {
        form.setData({
            cooperative_ids: assignedIds,
            visible_cooperative_ids: visibleIds,
        });
        // IDs berubah ketika filter atau halaman pagination berubah.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [assignedIds, visibleIds]);
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
            <AssignmentFilters
                picId={pic.id}
                filters={filters}
                regions={regions}
            />
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put(`/pics/${pic.id}/assignments`, {
                        preserveScroll: true,
                    });
                }}
                className="space-y-4"
            >
                <div className="overflow-x-auto border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox
                                        checked={allChecked}
                                        onCheckedChange={(checked) =>
                                            toggleAll(checked === true)
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
                                            onCheckedChange={(checked) =>
                                                toggle(
                                                    cooperative.id,
                                                    checked === true,
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
