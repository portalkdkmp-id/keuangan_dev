import { Head, useForm } from '@inertiajs/react';
import { SubmissionForm } from '@/components/Submissions/SubmissionForm';

export default function SubmissionsCreate({ cooperatives, categories }: any) {
    const form = useForm({ cooperative_id: cooperatives[0]?.id ?? '', title: '', purpose: '', needed_date: '', notes: '', items: [{ category_id: categories[0]?.id ?? '', description: '', quantity: '1', unit: '', unit_price: '0', notes: '' }] });
    return <div className="space-y-4 p-4"><Head title="Buat Pengajuan" /><h1 className="text-2xl font-semibold">Buat Pengajuan Dana</h1><SubmissionForm form={form} cooperatives={cooperatives} categories={categories} onSubmit={() => form.post('/submissions')} /></div>;
}
