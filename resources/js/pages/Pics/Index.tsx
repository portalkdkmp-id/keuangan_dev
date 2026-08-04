import { Head, Link, router } from '@inertiajs/react';
import { Building2, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
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

export default function Index({ pics, cities, filters }: any) {
    const [city, setCity] = useState(filters.city_id || 'all');
    const [status, setStatus] = useState(
        filters.is_active === undefined || filters.is_active === ''
            ? 'all'
            : String(filters.is_active),
    );
    return (
        <div className="space-y-5 p-4 sm:p-6">
            <Head title="PIC KDKMP" />
            <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">PIC KDKMP</h1>
                    <p className="text-sm text-muted-foreground">
                        Kelola user PIC dan assignment koperasi berdasarkan
                        wilayah.
                    </p>
                </div>
                <Button asChild>
                    <Link href="/pics/create">
                        <Plus className="size-4" /> Tambah PIC
                    </Link>
                </Button>
            </header>
            <form
                className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        '/pics',
                        {
                            search: new FormData(event.currentTarget).get(
                                'search',
                            ),
                            city_id: city === 'all' ? '' : city,
                            is_active: status === 'all' ? '' : status,
                        },
                        { preserveState: true },
                    );
                }}
            >
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Cari nama, email, atau telepon"
                />
                <Select value={city} onValueChange={setCity}>
                    <SelectTrigger>
                        <SelectValue placeholder="Semua wilayah" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua wilayah</SelectItem>
                        {cities.map((item: any) => (
                            <SelectItem key={item.id} value={item.id}>
                                {item.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger>
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua status</SelectItem>
                        <SelectItem value="1">Aktif</SelectItem>
                        <SelectItem value="0">Tidak aktif</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit">Terapkan Filter</Button>
            </form>
            <div className="overflow-x-auto border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama</TableHead>
                            <TableHead>Kontak</TableHead>
                            <TableHead>Wilayah</TableHead>
                            <TableHead>Koperasi</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {pics.data.map((pic: any) => (
                            <TableRow key={pic.id}>
                                <TableCell className="font-medium">
                                    {pic.name}
                                </TableCell>
                                <TableCell>
                                    <div>{pic.email}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {pic.phone || '-'}
                                    </div>
                                </TableCell>
                                <TableCell>{pic.city?.name ?? '-'}</TableCell>
                                <TableCell>
                                    {pic.assigned_cooperatives_count}
                                </TableCell>
                                <TableCell>
                                    {pic.is_active ? 'Aktif' : 'Tidak aktif'}
                                </TableCell>
                                <TableCell>
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={`/pics/${pic.id}/assignments`}
                                            >
                                                <Building2 className="size-4" />{' '}
                                                Assign Koperasi
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            size="icon"
                                            variant="ghost"
                                        >
                                            <Link
                                                href={`/pics/${pic.id}/edit`}
                                                aria-label={`Edit ${pic.name}`}
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                        {pics.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    PIC tidak ditemukan.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <SimplePagination meta={pics} />
        </div>
    );
}
