import { router } from '@inertiajs/react';
import { Check, MapPin, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export function AssignmentFilters({ picId, filters, regions }: any) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [assignment, setAssignment] = useState(filters.assignment || 'all');
    const [districtId, setDistrictId] = useState(filters.district_id ?? '');
    const [villageId, setVillageId] = useState(filters.village_id ?? '');
    const [regionOpen, setRegionOpen] = useState(false);
    const [regionSearch, setRegionSearch] = useState('');
    const selectedDistrict = regions.find(
        (district: any) => district.id === districtId,
    );
    const selectedVillage = selectedDistrict?.villages?.find(
        (village: any) => village.id === villageId,
    );
    const regionLabel = selectedVillage
        ? `${selectedVillage.name}, ${selectedDistrict.name}`
        : (selectedDistrict?.name ?? 'Semua wilayah');
    const visibleRegions = useMemo(() => {
        const needle = regionSearch.trim().toLocaleLowerCase('id-ID');

        if (!needle) {
            return regions;
        }

        return regions
            .map((district: any) => ({
                ...district,
                villages: (district.villages ?? []).filter((village: any) =>
                    `${village.name} ${district.name}`
                        .toLocaleLowerCase('id-ID')
                        .includes(needle),
                ),
            }))
            .filter(
                (district: any) =>
                    district.name.toLocaleLowerCase('id-ID').includes(needle) ||
                    district.villages.length,
            );
    }, [regionSearch, regions]);

    const apply = () =>
        router.get(
            `/pics/${picId}/assignments`,
            {
                search: search || undefined,
                assignment: assignment === 'all' ? undefined : assignment,
                district_id: districtId || undefined,
                village_id: villageId || undefined,
            },
            { preserveState: true, replace: true },
        );

    return (
        <Card size="sm">
            <CardHeader>
                <CardTitle>Filter Koperasi</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                    className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_280px_auto] lg:items-end"
                >
                    <div className="space-y-1.5">
                        <Label htmlFor="assignment-search">Pencarian</Label>
                        <Input
                            id="assignment-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama atau NIK koperasi"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label>Status assignment</Label>
                        <Select
                            value={assignment}
                            onValueChange={setAssignment}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua assignment
                                </SelectItem>
                                <SelectItem value="assigned">
                                    Sudah diassign
                                </SelectItem>
                                <SelectItem value="unassigned">
                                    Belum diassign
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5">
                        <Label>Wilayah</Label>
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full justify-between font-normal"
                            onClick={() => setRegionOpen(true)}
                        >
                            <span className="truncate">{regionLabel}</span>
                            <MapPin className="size-4 text-muted-foreground" />
                        </Button>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Terapkan</Button>
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            title="Reset filter"
                            onClick={() =>
                                router.get(`/pics/${picId}/assignments`)
                            }
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                </form>
            </CardContent>
            <Dialog open={regionOpen} onOpenChange={setRegionOpen}>
                <DialogContent className="bg-white sm:max-w-xl dark:bg-popover">
                    <DialogHeader>
                        <DialogTitle>Pilih Wilayah</DialogTitle>
                        <DialogDescription>
                            Cari kecamatan atau desa pada wilayah PIC.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            autoFocus
                            value={regionSearch}
                            onChange={(event) =>
                                setRegionSearch(event.target.value)
                            }
                            className="pl-9"
                            placeholder="Cari kecamatan atau desa"
                        />
                    </div>
                    <div className="max-h-80 overflow-y-auto border">
                        <button
                            type="button"
                            className="flex w-full items-center justify-between bg-white px-3 py-2 text-left text-sm hover:bg-muted dark:bg-popover"
                            onClick={() => {
                                setDistrictId('');
                                setVillageId('');
                                setRegionOpen(false);
                            }}
                        >
                            <span>Semua wilayah</span>
                            {!districtId && <Check className="size-4" />}
                        </button>
                        {visibleRegions.map((district: any) => (
                            <div key={district.id} className="border-t">
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between bg-white px-3 py-2 text-left text-sm font-medium hover:bg-muted dark:bg-popover"
                                    onClick={() => {
                                        setDistrictId(district.id);
                                        setVillageId('');
                                        setRegionOpen(false);
                                    }}
                                >
                                    <span>Kecamatan {district.name}</span>
                                    {districtId === district.id &&
                                        !villageId && (
                                            <Check className="size-4" />
                                        )}
                                </button>
                                {(district.villages ?? []).map(
                                    (village: any) => (
                                        <button
                                            key={village.id}
                                            type="button"
                                            className="flex w-full items-center justify-between bg-white py-2 pr-3 pl-7 text-left text-sm hover:bg-muted dark:bg-popover"
                                            onClick={() => {
                                                setDistrictId(district.id);
                                                setVillageId(village.id);
                                                setRegionOpen(false);
                                            }}
                                        >
                                            <span>{village.name}</span>
                                            {villageId === village.id && (
                                                <Check className="size-4" />
                                            )}
                                        </button>
                                    ),
                                )}
                            </div>
                        ))}
                        {!visibleRegions.length && (
                            <p className="bg-white p-6 text-center text-sm text-muted-foreground dark:bg-popover">
                                Wilayah tidak ditemukan.
                            </p>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
