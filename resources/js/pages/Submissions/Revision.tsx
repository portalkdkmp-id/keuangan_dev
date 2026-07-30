import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionForm } from '@/components/Submissions/SubmissionForm';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';

export default function SubmissionsRevision({ submission, cooperatives, requestCategories, requestTypes, bankAccounts }: any) {
    const revision = submission.open_revision_request ?? submission.openRevisionRequest;
    const firstItem = submission.items?.[0];
    const form = useForm({
        cooperative_id: submission.cooperative_id,
        title: submission.title ?? '',
        submission_request_category_id: submission.submission_request_category_id ?? '',
        submission_request_type_id: submission.submission_request_type_id ?? '',
        recipient_bank_account_id: submission.recipient_bank_account_id ?? bankAccounts[0]?.id ?? '',
        amount: firstItem?.unit_price ?? submission.total_amount ?? '',
        needed_date: submission.needed_date ?? '',
        notes: submission.notes ?? '',
    });
    const responseForm = useForm({ message: '' });

    return <div className="space-y-4 p-4"><Head title={`Revisi ${submission.submission_number}`} />
        <div className="flex items-center justify-between"><div><h1 className="text-2xl font-semibold">Revisi Pengajuan Dana</h1><p className="text-sm text-muted-foreground">{submission.submission_number}</p></div><SubmissionStatusBadge status={submission.status} /></div>
        {revision && <div className="space-y-2 rounded-md border p-4 text-sm">
            <h2 className="font-semibold">{revision.subject}</h2>
            <p>{revision.message}</p>
            {revision.fields?.length > 0 && <p className="text-muted-foreground">Field: {revision.fields.join(', ')}</p>}
        </div>}
        <SubmissionForm form={form} cooperatives={cooperatives} requestCategories={requestCategories} requestTypes={requestTypes} bankAccounts={bankAccounts} onSubmit={() => form.put(`/submissions/${submission.id}/revision`)} submitLabel="Simpan Revisi" />
        <SubmissionAttachments submission={submission} editable />
        <form onSubmit={(e) => { e.preventDefault(); responseForm.post(`/submissions/${submission.id}/resubmit`); }} className="space-y-3 rounded-md border p-4">
            <Label>Catatan balasan ke finance</Label>
            <textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={responseForm.data.message} onChange={(e) => responseForm.setData('message', e.target.value)} />
            <div className="flex gap-2"><Button disabled={responseForm.processing}>Kirim Ulang</Button><Button type="button" variant="outline" onClick={() => router.visit(`/submissions/${submission.id}`)}>Lihat Detail</Button></div>
        </form>
    </div>;
}
