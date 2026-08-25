import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { BackButton } from '@/components/back-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { ExportSingleSubmissionButton } from '@/components/Submissions/ExportSingleSubmissionButton';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import { ReimbursementDetail } from '@/components/Reimbursements/ReimbursementDetail';
import { AdvanceDetail } from '@/components/Advances/AdvanceDetail';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SubmissionItemsTable } from '@/components/Submissions/SubmissionItemsTable';

function dateInputValue(value?: string | null): string {
    return value ? value.slice(0, 10) : '';
}

export default function FinanceSubmissionsShow({
    submission,
    requestCategories,
    requestTypes,
}: any) {
    const [confirmForward, setConfirmForward] = useState(false);
    const detail = submission.finance_detail ?? submission.financeDetail ?? {};
    const firstItem = submission.items?.[0];
    const reviewForm = useForm({
        title: submission.title ?? '',
        submission_request_category_id:
            submission.submission_request_category_id ?? '',
        submission_request_type_id: submission.submission_request_type_id ?? '',
        amount: firstItem?.unit_price ?? submission.total_amount ?? '',
        needed_date: dateInputValue(submission.needed_date),
        notes: submission.notes ?? '',
        finance_notes: detail.finance_notes ?? '',
        rejection_reason: detail.rejection_reason ?? '',
    });
    const revisionForm = useForm({
        subject: 'Permintaan revisi pengajuan',
        message: '',
        fields: ['other'] as string[],
    });
    const rejectForm = useForm({
        rejection_reason: detail.rejection_reason ?? '',
    });
    const isReview = submission.status === 'finance_review';

    const saveReview = () =>
        reviewForm.put(`/finance/submissions/${submission.id}/finance-detail`);
    const submitToApproval = () => {
        setConfirmForward(false);
        reviewForm.put(`/finance/submissions/${submission.id}/finance-detail`, {
            onSuccess: () =>
                router.post(
                    `/finance/submissions/${submission.id}/forward-approval`,
                ),
        });
    };

    return (
        <div className="space-y-4 p-4">
            <Head title={submission.submission_number} />
            <BackButton fallback="/finance/submissions" />
            <div className="flex justify-end">
                <ExportSingleSubmissionButton id={submission.id} />
            </div>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {submission.submission_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Status review tetap finance_review sampai ada aksi
                        lanjutan.
                    </p>
                </div>
                <SubmissionStatusBadge status={submission.status} />
            </div>
            <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
                <div>Koperasi: {submission.cooperative?.name}</div>
                <div>Judul: {submission.title}</div>
                <div>Pengaju: {submission.submitter?.name}</div>
                <div>
                    Area:{' '}
                    {submission.submitter_city?.name ??
                        submission.submitterCity?.name ??
                        '-'}
                </div>
                <div>
                    Rekening:{' '}
                    {submission.recipient_bank_account
                        ? `${submission.recipient_bank_account.bank_name} - ${submission.recipient_bank_account.account_holder_name} - ${submission.recipient_bank_account.account_number}`
                        : '-'}
                </div>
                <div>Tanggal diajukan: {formatDate(submission.created_at)}</div>
                <div>
                    Tanggal dibutuhkan: {formatDate(submission.needed_date)}
                </div>
                <div>
                    Direview staff:{' '}
                    {detail.staff_reviewed_at
                        ? formatDate(detail.staff_reviewed_at)
                        : '-'}
                </div>
                <div className="font-semibold md:col-span-2">
                    Nominal saat ini: {rupiah(submission.total_amount)}
                </div>
            </div>

            <div className="space-y-3">
                <h2 className="font-semibold">Item Pengajuan</h2>
                <SubmissionItemsTable items={submission.items} />
            </div>

            <SubmissionAttachments submission={submission} />
            <AdvanceDetail detail={submission.advance_detail} />
            <ReimbursementDetail detail={submission.reimbursement_detail} />

            {submission.status === 'submitted' && (
                <Button
                    onClick={() =>
                        router.post(
                            `/finance/submissions/${submission.id}/start-review`,
                        )
                    }
                >
                    Mulai Review
                </Button>
            )}

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    saveReview();
                }}
                className="space-y-4 rounded-md border p-4"
            >
                <h2 className="font-semibold">Review Pengajuan</h2>
                <div className="grid gap-3 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Label>Title pengajuan</Label>
                        <Input
                            value={reviewForm.data.title}
                            onChange={(e) =>
                                reviewForm.setData('title', e.target.value)
                            }
                            disabled={!isReview}
                        />
                    </div>
                    <div>
                        <Label>Kategori pengajuan</Label>
                        <Select
                            value={
                                reviewForm.data
                                    .submission_request_category_id || undefined
                            }
                            onValueChange={(value) =>
                                reviewForm.setData(
                                    'submission_request_category_id',
                                    value,
                                )
                            }
                            disabled={!isReview}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                {requestCategories.map((category: any) => (
                                    <SelectItem
                                        key={category.id}
                                        value={category.id}
                                    >
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Jenis pengajuan</Label>
                        <Select
                            value={
                                reviewForm.data.submission_request_type_id ||
                                undefined
                            }
                            onValueChange={(value) =>
                                reviewForm.setData(
                                    'submission_request_type_id',
                                    value,
                                )
                            }
                            disabled={!isReview}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pilih jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                {requestTypes.map((type: any) => (
                                    <SelectItem key={type.id} value={type.id}>
                                        {type.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Nominal pengajuan</Label>
                        <MoneyInput
                            value={reviewForm.data.amount}
                            onChange={(value) =>
                                reviewForm.setData('amount', value)
                            }
                            disabled={!isReview}
                        />
                    </div>
                    <div>
                        <Label>Tanggal dibutuhkan</Label>
                        <Input
                            type="date"
                            value={reviewForm.data.needed_date ?? ''}
                            onChange={(e) =>
                                reviewForm.setData(
                                    'needed_date',
                                    e.target.value,
                                )
                            }
                            disabled={!isReview}
                        />
                    </div>
                    <div className="md:col-span-2">
                        <Label>Catatan PIC</Label>
                        <Textarea
                            className="min-h-24"
                            value={reviewForm.data.notes ?? ''}
                            onChange={(e) =>
                                reviewForm.setData('notes', e.target.value)
                            }
                            disabled={!isReview}
                        />
                    </div>
                    <div className="md:col-span-2">
                        <Label>Catatan staff keuangan</Label>
                        <Textarea
                            className="min-h-24"
                            value={reviewForm.data.finance_notes ?? ''}
                            onChange={(e) =>
                                reviewForm.setData(
                                    'finance_notes',
                                    e.target.value,
                                )
                            }
                            disabled={!isReview}
                        />
                    </div>
                </div>
                {isReview && (
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/finance/submissions')}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            onClick={() => setConfirmForward(true)}
                            disabled={reviewForm.processing}
                        >
                            Ajukan ke Approval
                        </Button>
                    </div>
                )}
            </form>
            <Dialog open={confirmForward} onOpenChange={setConfirmForward}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan ke Approval?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pastikan data review dan nominal pengajuan sudah benar.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button
                            onClick={submitToApproval}
                            disabled={reviewForm.processing}
                        >
                            Ya, Ajukan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {isReview && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        revisionForm.post(
                            `/finance/submissions/${submission.id}/request-revision`,
                        );
                    }}
                    className="space-y-3 rounded-md border p-4"
                >
                    <h2 className="font-semibold">Request Revisi</h2>
                    <Label>Catatan revisi</Label>
                    <Textarea
                        className="min-h-24"
                        value={revisionForm.data.message}
                        onChange={(e) =>
                            revisionForm.setData('message', e.target.value)
                        }
                    />
                    <Button
                        variant="outline"
                        disabled={revisionForm.processing}
                    >
                        Request Revisi
                    </Button>
                </form>
            )}

            {isReview && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        rejectForm.post(
                            `/finance/submissions/${submission.id}/reject`,
                        );
                    }}
                    className="space-y-3 rounded-md border p-4"
                >
                    <h2 className="font-semibold">Tolak Pengajuan</h2>
                    <Label>Alasan penolakan</Label>
                    <Textarea
                        className="min-h-24"
                        value={rejectForm.data.rejection_reason}
                        onChange={(e) =>
                            rejectForm.setData(
                                'rejection_reason',
                                e.target.value,
                            )
                        }
                    />
                    <Button variant="outline" disabled={rejectForm.processing}>
                        Tolak Pengajuan
                    </Button>
                </form>
            )}

            {/* <SubmissionTimeline histories={submission.status_histories ?? submission.statusHistories ?? []} /> */}
        </div>
    );
}
