import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function SubmissionTypeIndex({ types }: any) {
    return <div className="space-y-4 p-4"><Head title="Jenis Pengajuan" />
        <div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">Jenis Pengajuan</h1><Button asChild><Link href="/submission-types/create">Tambah</Link></Button></div>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader><TableBody>{types.data.map((type: any) => <TableRow key={type.id}><TableCell>{type.name}</TableCell><TableCell>{type.is_active ? 'Aktif' : 'Nonaktif'}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/submission-types/${type.id}/edit`}>Edit</Link></Button><Button size="sm" variant="outline" onClick={() => router.delete(`/submission-types/${type.id}`)}>Hapus</Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
