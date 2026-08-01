import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';

export default function FinanceApprovalRevisionShow({ submission, requestCategories, requestTypes }: any) {
    const review = submission.approval_reviews?.[0];
    const firstItem = submission.items?.[0];
    const form = useForm({ title: submission.title ?? '', submission_request_category_id: submission.submission_request_category_id ?? '', submission_request_type_id: submission.submission_request_type_id ?? '', amount: firstItem?.unit_price ?? submission.total_amount ?? '', needed_date: submission.needed_date ?? '', notes: submission.notes ?? '', finance_notes: submission.finance_detail?.finance_notes ?? '' });
    const resubmitForm = useForm({ change_summary: '', notes: '' });
    return <div className="space-y-4 p-4"><Head title={submission.submission_number} /><h1 className="text-2xl font-semibold">Revisi Approval {submission.submission_number}</h1>
        <div className="rounded-md border p-4 text-sm"><h2 className="font-semibold">{review?.revision_subject}</h2><p>{review?.revision_message}</p><p className="text-muted-foreground">{review?.revision_fields?.join(', ')}</p></div>
        <form onSubmit={(e) => { e.preventDefault(); form.put(`/finance/approval-revisions/${submission.id}`); }} className="grid gap-3 rounded-md border p-4 md:grid-cols-2">
            <div className="md:col-span-2"><Label>Title</Label><Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} /></div>
            <div><Label>Kategori</Label><Select value={form.data.submission_request_category_id || undefined} onValueChange={(v) => form.setData('submission_request_category_id', v)}><SelectTrigger className="w-full"><SelectValue /></SelectTrigger><SelectContent>{requestCategories.map((c: any) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select></div>
            <div><Label>Jenis</Label><Select value={form.data.submission_request_type_id || undefined} onValueChange={(v) => form.setData('submission_request_type_id', v)}><SelectTrigger className="w-full"><SelectValue /></SelectTrigger><SelectContent>{requestTypes.map((t: any) => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}</SelectContent></Select></div>
            <div><Label>Nominal</Label><Input type="number" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} /></div>
            <div><Label>Tanggal dibutuhkan</Label><Input type="date" value={String(form.data.needed_date ?? '').slice(0, 10)} onChange={(e) => form.setData('needed_date', e.target.value)} /></div>
            <div><Label>Catatan PIC</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={form.data.notes ?? ''} onChange={(e) => form.setData('notes', e.target.value)} /></div>
            <div><Label>Catatan Finance</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={form.data.finance_notes ?? ''} onChange={(e) => form.setData('finance_notes', e.target.value)} /></div>
            <Button>Simpan Perbaikan</Button>
        </form>
        <SubmissionAttachments submission={submission} />
        <form onSubmit={(e) => { e.preventDefault(); resubmitForm.post(`/finance/approval-revisions/${submission.id}/resubmit`); }} className="space-y-3 rounded-md border p-4"><Label>Ringkasan perubahan</Label><textarea className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={resubmitForm.data.change_summary} onChange={(e) => resubmitForm.setData('change_summary', e.target.value)} /><Label>Catatan ke approver</Label><textarea className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={resubmitForm.data.notes} onChange={(e) => resubmitForm.setData('notes', e.target.value)} /><Button>Kirim Ulang ke Approval</Button><Button type="button" variant="outline" onClick={() => router.visit('/finance/approval-revisions')}>Cancel</Button></form>
    </div>;
}
