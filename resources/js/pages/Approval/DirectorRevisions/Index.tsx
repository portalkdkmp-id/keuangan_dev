import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';

export default function ApprovalDirectorRevisionsIndex({ submissions }: any) {
    return <div className="space-y-4 p-4"><Head title="Revisi Director" /><h1 className="text-2xl font-semibold">Revisi dari Finance Director</h1>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Nomor</TableHead><TableHead>Title</TableHead><TableHead>Koperasi</TableHead><TableHead>Judul Revisi</TableHead><TableHead>Tanggal</TableHead><TableHead /></TableRow></TableHeader><TableBody>{submissions.data.map((s: any) => {
            const review = s.director_reviews?.[0];

            return <TableRow key={s.id}><TableCell>{s.submission_number}</TableCell><TableCell>{s.title}</TableCell><TableCell>{s.cooperative?.name}</TableCell><TableCell>{review?.revision_subject ?? '-'}</TableCell><TableCell>{formatDate(s.last_director_revision_requested_at)}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/approval/director-revisions/${s.id}`}>Buka</Link></Button></TableCell></TableRow>;
        })}</TableBody></Table></div>
    </div>;
}
