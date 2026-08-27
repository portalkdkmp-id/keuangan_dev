import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { BackButton } from '@/components/back-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { ExportSingleSubmissionButton } from '@/components/Submissions/ExportSingleSubmissionButton';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
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
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import { Checkbox } from '@/components/ui/checkbox';

export default function ApprovalSubmissionsShow({ submission }: any) {
    const [confirmApprove, setConfirmApprove] = useState(false);
    const review =
        submission.active_approval_review ??
        submission.activeApprovalReview ??
        submission.approval_reviews?.[0];
    const startReviewForm = useForm({});
    const approveForm = useForm({
        approved_amount: review?.submitted_amount ?? submission.total_amount,
        notes: '',
        is_urgent: submission.is_urgent ?? false,
    });
    const rejectForm = useForm({ rejection_reason: '', notes: '' });
    const revisionForm = useForm({ revision_message: '' });
    const urgencyForm = useForm({ is_urgent: submission.is_urgent ?? false });

    return (
        <div className="space-y-4 p-4">
            <Head title={submission.submission_number} />
            <BackButton fallback="/approval/submissions" />
            <div className="flex justify-end">
                <ExportSingleSubmissionButton id={submission.id} />
            </div>
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {submission.submission_number}
                    </h1>
                    <p>{submission.title}</p>
                </div>
                <SubmissionStatusBadge status={submission.status} />
            </div>
            <div className="grid gap-3 rounded-md border p-4 text-sm md:grid-cols-2">
                <div>PIC: {submission.submitter?.name}</div>
                <div>Koperasi: {submission.cooperative?.name}</div>
                <div>Area: {submission.submitter_city?.name ?? '-'}</div>
                <div>Kategori: {submission.request_category?.name ?? '-'}</div>
                <div>Jenis: {submission.request_type?.name ?? '-'}</div>
                <div>
                    Tanggal dibutuhkan: {formatDate(submission.needed_date)}
                </div>
                <div>
                    Nominal finance:{' '}
                    {rupiah(
                        review?.submitted_amount ?? submission.total_amount,
                    )}
                </div>
                <div>
                    Catatan finance:{' '}
                    {submission.finance_detail?.finance_notes ?? '-'}
                </div>
                <div>Urgensi: {submission.is_urgent ? 'Urgent' : 'Normal'}</div>
                <div>
                    Rekening snapshot:{' '}
                    {submission.bank_name_snapshot ??
                        submission.recipient_bank_account?.bank_name ??
                        '-'}{' '}
                    -{' '}
                    {submission.bank_account_holder_snapshot ??
                        submission.recipient_bank_account
                            ?.account_holder_name ??
                        '-'}
                </div>
                <div>
                    Nomor rekening:{' '}
                    {submission.bank_account_number_snapshot ??
                        submission.recipient_bank_account?.account_number ??
                        '-'}
                </div>
            </div>
            <SubmissionAttachments submission={submission} />
            <AdvanceDetail detail={submission.advance_detail} />
            {submission.status === 'approval_review' && (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        startReviewForm.post(
                            `/approval/submissions/${submission.id}/start-review`,
                            { preserveScroll: true },
                        );
                    }}
                >
                    <Button type="submit" disabled={startReviewForm.processing}>
                        {startReviewForm.processing
                            ? 'Memulai...'
                            : 'Mulai Review'}
                    </Button>
                </form>
            )}
            {submission.status === 'approval_in_review' && (
                <div className="grid gap-4 lg:grid-cols-3">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            setConfirmApprove(true);
                        }}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <h2 className="font-semibold">Setujui</h2>
                        <Label>Nominal disetujui</Label>
                        <MoneyInput
                            value={approveForm.data.approved_amount}
                            onChange={(value) =>
                                approveForm.setData('approved_amount', value)
                            }
                        />
                        <Label>Catatan approval</Label>
                        <Textarea
                            className="min-h-24"
                            value={approveForm.data.notes}
                            onChange={(e) =>
                                approveForm.setData('notes', e.target.value)
                            }
                        />
                        <div className="flex items-center gap-3 rounded-md border p-3">
                            <Checkbox
                                id="approval-is-urgent"
                                checked={approveForm.data.is_urgent}
                                onCheckedChange={(value) => {
                                    const urgent = value === true;
                                    approveForm.setData('is_urgent', urgent);
                                    urgencyForm.setData('is_urgent', urgent);
                                }}
                            />
                            <Label htmlFor="approval-is-urgent">
                                Pengajuan Urgent
                            </Label>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={urgencyForm.processing}
                            onClick={() =>
                                urgencyForm.patch(
                                    `/approval/submissions/${submission.id}/urgency`,
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Simpan Urgensi
                        </Button>
                        <Button>Setujui dan Kirim ke Director</Button>
                    </form>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            revisionForm.post(
                                `/approval/submissions/${submission.id}/request-revision`,
                            );
                        }}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <h2 className="font-semibold">Minta Revisi</h2>
                        <Label>Catatan Revisi</Label>
                        <Textarea
                            className="min-h-24"
                            value={revisionForm.data.revision_message}
                            onChange={(e) =>
                                revisionForm.setData(
                                    'revision_message',
                                    e.target.value,
                                )
                            }
                        />
                        <Button variant="outline">Minta Revisi</Button>
                    </form>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            rejectForm.post(
                                `/approval/submissions/${submission.id}/reject`,
                            );
                        }}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <h2 className="font-semibold">Tolak</h2>
                        <Label>Alasan Penolakan</Label>
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
                        <Button variant="outline">Tolak Pengajuan</Button>
                    </form>
                </div>
            )}
            {submission.status === 'approval_revision_requested' && (
                <div className="rounded-md border p-4 text-sm">
                    Menunggu perbaikan Finance Staff.
                </div>
            )}
            {submission.status === 'director_review' && (
                <div className="rounded-md border p-4 text-sm">
                    Sudah disetujui dan diteruskan ke Finance Director. Nominal
                    disetujui: {rupiah(submission.approval_approved_amount)}
                </div>
            )}
            {submission.status === 'approval_rejected' && (
                <div className="rounded-md border p-4 text-sm">
                    Pengajuan ditolak. Alasan: {review?.rejection_reason ?? '-'}
                </div>
            )}
            {submission.disbursement && (
                <div className="grid gap-2 rounded-md border p-4 text-sm md:grid-cols-2">
                    <h2 className="font-semibold md:col-span-2">
                        Informasi Pencairan
                    </h2>
                    <div>
                        Nomor: {submission.disbursement.disbursement_number}
                    </div>
                    <div>Nominal: {rupiah(submission.disbursement.amount)}</div>
                    <div>
                        Penerima pertama:{' '}
                        {submission.disbursement.recipient_name_snapshot}
                    </div>
                    <div>
                        Jenis penerima: {submission.disbursement.recipient_type}
                    </div>
                    <div>
                        Tujuan:{' '}
                        {submission.disbursement.destination_bank_snapshot} -{' '}
                        {submission.disbursement
                            .destination_account_number_masked ?? '-'}
                    </div>
                    <div>
                        Status distribusi:{' '}
                        {submission.disbursement.distribution_status}
                    </div>
                    <div className="md:col-span-2">
                        Bukti:{' '}
                        {(submission.disbursement.attachments ?? []).map(
                            (attachment: any) => (
                                <a
                                    key={attachment.id}
                                    className="mr-3 underline"
                                    href={`/director/disbursement-attachments/${attachment.id}/download`}
                                >
                                    {attachment.original_name}
                                </a>
                            ),
                        )}
                    </div>
                </div>
            )}
            <SubmissionTimeline
                histories={
                    submission.status_histories ??
                    submission.statusHistories ??
                    []
                }
            />
            <ReimbursementDetail detail={submission.reimbursement_detail} />
            <Dialog open={confirmApprove} onOpenChange={setConfirmApprove}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Setujui pengajuan?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pengajuan akan diteruskan ke Finance Director. Pastikan
                        nominal dan catatan sudah benar.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button
                            disabled={approveForm.processing}
                            onClick={() => {
                                setConfirmApprove(false);
                                approveForm.post(
                                    `/approval/submissions/${submission.id}/approve`,
                                );
                            }}
                        >
                            Ya, Setujui
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
