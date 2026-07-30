import { Head, useForm } from '@inertiajs/react';
import { SubmissionForm } from '@/components/Submissions/SubmissionForm';

export default function SubmissionsCreate({ cooperatives, requestCategories, requestTypes, bankAccounts }: any) {
    const form = useForm({ title: '', cooperative_id: cooperatives[0]?.id ?? '', submission_request_category_id: '', submission_request_type_id: '', recipient_bank_account_id: bankAccounts[0]?.id ?? '', amount: '', needed_date: '', notes: '' });
    return <div className="space-y-4 p-4"><Head title="Buat Pengajuan" /><h1 className="text-2xl font-semibold">Buat Pengajuan Dana</h1><SubmissionForm form={form} cooperatives={cooperatives} requestCategories={requestCategories} requestTypes={requestTypes} bankAccounts={bankAccounts} onSubmit={() => form.post('/submissions')} /></div>;
}
