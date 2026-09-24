import { router } from '@inertiajs/react';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
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

type RegionOption = { id: string; name: string };

type RegionDialogSelectProps = {
    label: string;
    placeholder: string;
    options: RegionOption[];
    value: string;
    disabled?: boolean;
    onChange: (value: string) => void;
};

function RegionDialogSelect({
    label,
    placeholder,
    options,
    value,
    disabled = false,
    onChange,
}: RegionDialogSelectProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const selected = options.find((option) => option.id === value);
    const visibleOptions = useMemo(() => {
        const needle = search.trim().toLocaleLowerCase('id-ID');

        return needle
            ? options.filter((option) =>
                  option.name.toLocaleLowerCase('id-ID').includes(needle),
              )
            : options;
    }, [options, search]);

    const select = (nextValue: string) => {
        onChange(nextValue);
        setOpen(false);
        setSearch('');
    };

    return (
        <div className="min-w-0 space-y-1.5">
            <Label>{label}</Label>
            <Button
                type="button"
                variant="outline"
                className="w-full justify-between font-normal"
                disabled={disabled}
                onClick={() => setOpen(true)}
            >
                <span className="truncate">
                    {selected?.name ?? placeholder}
                </span>
                <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
            </Button>
            <Dialog
                open={open}
                onOpenChange={(nextOpen) => {
                    setOpen(nextOpen);

                    if (!nextOpen) {
                        setSearch('');
                    }
                }}
            >
                <DialogContent className="bg-white sm:max-w-lg dark:bg-popover">
                    <DialogHeader>
                        <DialogTitle>Pilih {label}</DialogTitle>
                        <DialogDescription>
                            Cari dan pilih {label.toLocaleLowerCase('id-ID')}.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            autoFocus
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="pl-9"
                            placeholder={`Cari ${label.toLocaleLowerCase('id-ID')}`}
                        />
                    </div>
                    <div className="max-h-80 overflow-y-auto border">
                        <button
                            type="button"
                            className="flex w-full items-center justify-between bg-white px-3 py-2 text-left text-sm hover:bg-muted dark:bg-popover"
                            onClick={() => select('')}
                        >
                            <span>{placeholder}</span>
                            {!value && <Check className="size-4" />}
                        </button>
                        {visibleOptions.map((option) => (
                            <button
                                key={option.id}
                                type="button"
                                className="flex w-full items-center justify-between border-t bg-white px-3 py-2 text-left text-sm hover:bg-muted dark:bg-popover"
                                onClick={() => select(option.id)}
                            >
                                <span>{option.name}</span>
                                {value === option.id && (
                                    <Check className="size-4" />
                                )}
                            </button>
                        ))}
                        {!visibleOptions.length && (
                            <p className="bg-white p-6 text-center text-sm text-muted-foreground dark:bg-popover">
                                Data tidak ditemukan.
                            </p>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function AssignmentFilters({ picId, filters, regions }: any) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [assignment, setAssignment] = useState(filters.assignment || 'all');
    const [provinceId, setProvinceId] = useState(filters.province_id ?? '');
    const [cityId, setCityId] = useState(filters.city_id ?? '');
    const [districtId, setDistrictId] = useState(filters.district_id ?? '');
    const [villageId, setVillageId] = useState(filters.village_id ?? '');
    const selectedProvince = regions.find(
        (province: any) => province.id === provinceId,
    );
    const cities = selectedProvince?.cities ?? [];
    const selectedCity = cities.find((city: any) => city.id === cityId);
    const districts = selectedCity?.districts ?? [];
    const selectedDistrict = districts.find(
        (district: any) => district.id === districtId,
    );
    const villages = selectedDistrict?.villages ?? [];

    const apply = () =>
        router.get(
            `/pics/${picId}/assignments`,
            {
                search: search || undefined,
                assignment: assignment === 'all' ? undefined : assignment,
                province_id: provinceId || undefined,
                city_id: cityId || undefined,
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
                    className="space-y-4"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="assignment-search">Pencarian</Label>
                            <Input
                                id="assignment-search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
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
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <RegionDialogSelect
                            label="Provinsi"
                            placeholder="Semua provinsi"
                            options={regions}
                            value={provinceId}
                            onChange={(value) => {
                                setProvinceId(value);
                                setCityId('');
                                setDistrictId('');
                                setVillageId('');
                            }}
                        />
                        <RegionDialogSelect
                            label="Kabupaten/Kota"
                            placeholder="Semua kabupaten/kota"
                            options={cities}
                            value={cityId}
                            disabled={!provinceId}
                            onChange={(value) => {
                                setCityId(value);
                                setDistrictId('');
                                setVillageId('');
                            }}
                        />
                        <RegionDialogSelect
                            label="Kecamatan"
                            placeholder="Semua kecamatan"
                            options={districts}
                            value={districtId}
                            disabled={!cityId}
                            onChange={(value) => {
                                setDistrictId(value);
                                setVillageId('');
                            }}
                        />
                        <RegionDialogSelect
                            label="Desa"
                            placeholder="Semua desa"
                            options={villages}
                            value={villageId}
                            disabled={!districtId}
                            onChange={setVillageId}
                        />
                    </div>
                    <div className="flex justify-end gap-2">
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
                        <Button type="submit">Terapkan Filter</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
