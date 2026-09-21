import { Head } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionRevisionHistory } from '@/components/Submissions/SubmissionRevisionHistory';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';

export default function SubmissionHistoryShow({ submission }: any) {
    return (
        <div className="space-y-5 p-4">
            <Head title={`History ${submission.submission_number}`} />
            <BackButton fallback="/submission-history" />
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div><h1 className="text-2xl font-semibold">{submission.submission_number}</h1><p>{submission.title}</p></div>
                <SubmissionStatusBadge status={submission.status} />
            </div>
            <section className="grid gap-3 border-y py-4 text-sm md:grid-cols-2">
                <div>Pengaju: <strong>{submission.submitter?.name ?? '-'}</strong></div>
                <div>Area: <strong>{submission.submitter_city?.name ?? '-'}</strong></div>
                <div>Koperasi: <strong>{submission.cooperative?.name ?? 'Internal'}</strong></div>
                <div>Kategori: <strong>{submission.request_category?.name ?? '-'}</strong></div>
                <div>Tanggal dibutuhkan: <strong>{formatDate(submission.needed_date)}</strong></div>
                <div>Total: <strong>{rupiah(submission.total_amount)}</strong></div>
            </section>
            <section className="space-y-2">
                <h2 className="font-semibold">Item Pengajuan</h2>
                <div className="overflow-x-auto rounded-md border"><Table><TableHeader><TableRow><TableHead>Nama Item</TableHead><TableHead>Jenis</TableHead><TableHead className="text-right">Nominal</TableHead></TableRow></TableHeader><TableBody>{submission.items.map((item: any) => <TableRow key={item.id}><TableCell>{item.description}</TableCell><TableCell>{item.request_type?.name ?? item.other_type_name ?? '-'}</TableCell><TableCell className="text-right">{rupiah(item.subtotal ?? item.unit_price)}</TableCell></TableRow>)}</TableBody></Table></div>
            </section>
            <SubmissionAttachments submission={submission} />
            <SubmissionRevisionHistory submission={submission} />
            <SubmissionTimeline histories={submission.status_histories ?? []} />
        </div>
    );
}
