import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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

type Option = { id: string; name: string };

export default function CooperativesCreate({
    provinces,
}: {
    provinces: Option[];
}) {
    const form = useForm({
        nik: '',
        name: '',
        province_id: '',
        city_id: '',
        district_id: '',
        village_id: '',
        latitude: '',
        longitude: '',
        is_active: true as boolean,
    });
    return (
        <CooperativeForm
            title="Create Cooperative"
            form={form}
            provinces={provinces}
            onSubmit={() => form.post('/cooperatives')}
        />
    );
}

export function CooperativeForm({ title, form, provinces, onSubmit }: any) {
    const [cities, setCities] = useState<Option[]>([]);
    const [districts, setDistricts] = useState<Option[]>([]);
    const [villages, setVillages] = useState<Option[]>([]);
    useEffect(() => {
        if (form.data.province_id)
            fetch(`/regions/cities?province_id=${form.data.province_id}`)
                .then((r) => r.json())
                .then(setCities);
    }, [form.data.province_id]);
    useEffect(() => {
        if (form.data.city_id)
            fetch(`/regions/districts?city_id=${form.data.city_id}`)
                .then((r) => r.json())
                .then(setDistricts);
    }, [form.data.city_id]);
    useEffect(() => {
        if (form.data.district_id)
            fetch(`/regions/villages?district_id=${form.data.district_id}`)
                .then((r) => r.json())
                .then(setVillages);
    }, [form.data.district_id]);
    return (
        <div className="max-w-3xl space-y-4 p-4">
            <Head title={title} />
            <h1 className="text-2xl font-semibold">{title}</h1>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    onSubmit();
                }}
                className="grid gap-4 md:grid-cols-2"
            >
                <div>
                    <Label>NIK</Label>
                    <Input
                        value={form.data.nik}
                        onChange={(e) => form.setData('nik', e.target.value)}
                    />
                </div>
                <div>
                    <Label>Name</Label>
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </div>
                <RegionSelect
                    label="Province"
                    value={form.data.province_id}
                    options={provinces}
                    onChange={(v: string) => form.setData('province_id', v)}
                />
                <RegionSelect
                    label="City"
                    value={form.data.city_id}
                    options={cities}
                    onChange={(v: string) => form.setData('city_id', v)}
                />
                <RegionSelect
                    label="District"
                    value={form.data.district_id}
                    options={districts}
                    onChange={(v: string) => form.setData('district_id', v)}
                />
                <RegionSelect
                    label="Village"
                    value={form.data.village_id}
                    options={villages}
                    onChange={(v: string) => form.setData('village_id', v)}
                />
                <div>
                    <Label>Latitude</Label>
                    <Input
                        value={form.data.latitude}
                        onChange={(e) =>
                            form.setData('latitude', e.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Longitude</Label>
                    <Input
                        value={form.data.longitude}
                        onChange={(e) =>
                            form.setData('longitude', e.target.value)
                        }
                    />
                </div>
                <label className="flex gap-2 text-sm md:col-span-2">
                    <input
                        type="checkbox"
                        checked={form.data.is_active}
                        onChange={(e) =>
                            form.setData('is_active', e.target.checked)
                        }
                    />{' '}
                    Active
                </label>
                <div className="flex gap-2 md:col-span-2">
                    <Button type="submit">Save</Button>
                    <Button variant="outline" asChild>
                        <Link href="/cooperatives">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}

function RegionSelect({ label, value, options, onChange }: any) {
    return (
        <div>
            <Label>{label}</Label>
            <Select value={value || undefined} onValueChange={onChange}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    {options.map((o: Option) => (
                        <SelectItem key={o.id} value={o.id}>
                            {o.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
