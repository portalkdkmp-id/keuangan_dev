import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function SubmissionCategoryIndex({ categories }: any) {
    return <div className="space-y-4 p-4"><Head title="Kategori Pengajuan" />
        <div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">Kategori Pengajuan</h1><Button asChild><Link href="/submission-categories/create">Tambah</Link></Button></div>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader><TableBody>{categories.data.map((category: any) => <TableRow key={category.id}><TableCell>{category.name}</TableCell><TableCell>{category.is_active ? 'Aktif' : 'Nonaktif'}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/submission-categories/${category.id}/edit`}>Edit</Link></Button><Button size="sm" variant="outline" onClick={() => router.delete(`/submission-categories/${category.id}`)}>Hapus</Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
