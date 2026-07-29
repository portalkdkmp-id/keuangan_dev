import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';

export default function SubmissionsIndex({ submissions, filters }: any) {
    return <div className="space-y-4 p-4"><Head title="Pengajuan Dana" />
        <div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">Pengajuan Dana</h1><Button asChild><Link href="/submissions/create">Buat Draft</Link></Button></div>
        <form onSubmit={(e) => { e.preventDefault(); router.get('/submissions', Object.fromEntries(new FormData(e.currentTarget)), { preserveState: true }); }} className="flex gap-2">
            <Input name="search" defaultValue={filters.search ?? ''} placeholder="Cari nomor, judul, koperasi" />
            <Button type="submit">Filter</Button>
        </form>
        <div className="overflow-hidden rounded-md border"><table className="w-full text-sm"><thead><tr className="border-b bg-muted"><th className="p-3 text-left">Nomor</th><th>Judul</th><th>Koperasi</th><th>Total</th><th>Status</th><th /></tr></thead><tbody>{submissions.data.map((s: any) => <tr key={s.id} className="border-b"><td className="p-3">{s.submission_number}</td><td>{s.title}</td><td>{s.cooperative?.name}</td><td>{rupiah(s.total_amount)}</td><td><SubmissionStatusBadge status={s.status} /></td><td className="p-3 text-right"><Button size="sm" variant="outline" asChild><Link href={`/submissions/${s.id}`}>Lihat</Link></Button></td></tr>)}</tbody></table></div>
    </div>;
}
