import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function CooperativesIndex({
    cooperatives,
    provinces,
    filters,
}: any) {
    const [provinceId, setProvinceId] = useState(filters.province_id || 'all');
    const [status, setStatus] = useState(filters.is_active || 'all');
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            '/cooperatives',
            Object.fromEntries(new FormData(event.currentTarget)),
            { preserveState: true },
        );
    };
    return (
        <div className="space-y-4 p-4">
            <Head title="Cooperatives" />
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Cooperatives</h1>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/cooperatives/import">Import</Link>
                    </Button>
                    <Button asChild>
                        <Link href="/cooperatives/create">Create</Link>
                    </Button>
                </div>
            </div>
            <form onSubmit={submit} className="grid gap-2 md:grid-cols-4">
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Search NIK or name"
                />
                <input
                    type="hidden"
                    name="province_id"
                    value={provinceId === 'all' ? '' : provinceId}
                />
                <Select value={provinceId} onValueChange={setProvinceId}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="All provinces" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All provinces</SelectItem>
                        {provinces.map((p: any) => (
                            <SelectItem key={p.id} value={p.id}>
                                {p.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <input
                    type="hidden"
                    name="is_active"
                    value={status === 'all' ? '' : status}
                />
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="All status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All status</SelectItem>
                        <SelectItem value="1">Active</SelectItem>
                        <SelectItem value="0">Inactive</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit">Filter</Button>
            </form>
            <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>NIK</TableHead><TableHead>Name</TableHead><TableHead>Region</TableHead><TableHead>PIC</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader><TableBody>{cooperatives.data.map((item: any) => <TableRow key={item.id}><TableCell>{item.nik}</TableCell><TableCell>{item.name}</TableCell><TableCell>{item.city?.name}, {item.province?.name}</TableCell><TableCell>{item.pics?.[0]?.name ?? '-'} ({item.pics_count})</TableCell><TableCell>{item.is_active ? 'Active' : 'Inactive'}</TableCell><TableCell className="text-right"><Button variant="outline" size="sm" asChild><Link href={`/cooperatives/${item.id}`}>Open</Link></Button></TableCell></TableRow>)}</TableBody></Table></div>
        </div>
    );
}
