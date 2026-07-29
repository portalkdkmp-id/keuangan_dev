import { Head, useForm } from '@inertiajs/react';
import { SubmissionForm } from '@/components/Submissions/SubmissionForm';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';

export default function SubmissionsEdit({ submission, cooperatives, categories }: any) {
    const form = useForm({ cooperative_id: submission.cooperative_id, title: submission.title, purpose: submission.purpose, needed_date: submission.needed_date ?? '', notes: submission.notes ?? '', items: submission.items.map((i: any) => ({ category_id: i.category_id, description: i.description, quantity: i.quantity, unit: i.unit ?? '', unit_price: i.unit_price, notes: i.notes ?? '' })) });
    return <div className="space-y-4 p-4"><Head title="Edit Pengajuan" /><h1 className="text-2xl font-semibold">Edit Pengajuan Dana</h1><SubmissionForm form={form} cooperatives={cooperatives} categories={categories} onSubmit={() => form.put(`/submissions/${submission.id}`)} /><SubmissionAttachments submission={submission} editable /></div>;
}
