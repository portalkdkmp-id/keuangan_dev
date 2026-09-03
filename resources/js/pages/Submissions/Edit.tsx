import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SubmissionForm } from '@/components/Submissions/SubmissionForm';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';

export default function SubmissionsEdit({
    submission,
    cooperatives,
    requestCategories,
    requestTypes,
    bankAccounts,
    canSubmitInternal,
    submitter,
}: any) {
    const form = useForm({
        title: submission.title ?? '',
        cooperative_id: submission.cooperative_id,
        submission_request_category_id:
            submission.submission_request_category_id ?? '',
        items: submission.items.map((item: any) => ({
            id: item.id,
            name: item.description,
            request_type_id:
                item.request_type_id ??
                submission.submission_request_type_id ??
                '',
            other_type_name: item.other_type_name ?? '',
            amount: String(Math.trunc(Number(item.unit_price))),
        })),
        recipient_bank_account_id:
            submission.recipient_bank_account_id ?? bankAccounts[0]?.id ?? '',
        needed_date: submission.needed_date ?? '',
        notes: submission.notes ?? '',
        is_urgent: submission.is_urgent ?? false,
    });
    return (
        <div className="space-y-4 p-4">
            <Head title="Edit Pengajuan" />
            <h1 className="text-2xl font-semibold">Edit Pengajuan Dana</h1>
            <SubmissionForm
                form={form}
                cooperatives={cooperatives}
                requestCategories={requestCategories}
                requestTypes={requestTypes}
                bankAccounts={bankAccounts}
                canSubmitInternal={canSubmitInternal}
                submitter={submitter}
                onSubmit={() => form.put(`/submissions/${submission.id}`)}
            />
            <SubmissionAttachments submission={submission} editable />
            <div className="flex justify-end">
                <Button asChild>
                    <Link href={`/submissions/${submission.id}/review`}>
                        Review Pengajuan
                    </Link>
                </Button>
            </div>
        </div>
    );
}
