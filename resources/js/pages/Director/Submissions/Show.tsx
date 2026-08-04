import { Head, router, useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { useState } from 'react';
import { BackButton } from '@/components/back-button';
import { MoneyInput } from '@/components/Submissions/MoneyInput';
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionStatusBadge } from '@/components/Submissions/SubmissionStatusBadge';
import { SubmissionTimeline } from '@/components/Submissions/SubmissionTimeline';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { ReimbursementDetail } from '@/components/Reimbursements/ReimbursementDetail';
import { AdvanceDetail } from '@/components/Advances/AdvanceDetail';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';

const normalizeDateTime = (value: string) =>
    value
        ? `${value.replace('T', ' ')}${value.length === 16 ? ':00' : ''}`
        : '';
const initialPayment = {
    transfer_date: '',
    transferred_at: '',
    payment_method: 'bank_transfer',
    source_company_bank_account_id: '',
    recipient_type: 'pic_kdkmp',
    recipient_user_id: '',
    recipient_cooperative_id: '',
    destination_bank_account_id: '',
    destination_bank_name: '',
    destination_account_number: '',
    destination_account_holder: '',
    recipient_name: '',
    transaction_reference: '',
    notes: '',
    attachments: [] as File[],
};

export default function DirectorSubmissionsShow({
    submission,
    companyBankAccounts = [],
    financeStaff = [],
}: any) {
    const directorReview = submission.director_reviews?.[0];
    const approvalReview = submission.approval_reviews?.[0];
    const [accountDialogOpen, setAccountDialogOpen] = useState(false);
    const startForm = useForm({});
    const pendingForm = useForm({
        approved_amount: submission.approval_approved_amount ?? '',
        notes: '',
    });
    const disburseNowForm = useForm({
        approved_amount: submission.approval_approved_amount ?? '',
        ...initialPayment,
        recipient_user_id: submission.submitted_by,
    });
    const disburseForm = useForm({
        ...initialPayment,
        recipient_user_id: submission.submitted_by,
    });
    const rejectForm = useForm({ rejection_reason: '' });
    const revisionForm = useForm({ revision_message: '' });
    const accountForm = useForm({
        bank_name: '',
        account_number: '',
        account_holder_name: '',
        description: '',
        is_active: true,
        is_primary: false,
    });
    const files = (event: ChangeEvent<HTMLInputElement>) =>
        Array.from(event.target.files ?? []);
    const send = (form: any, url: string) => {
        form.transform((data: any) => ({
            ...data,
            transfer_date: data.transferred_at?.slice(0, 10),
            transferred_at: normalizeDateTime(data.transferred_at),
        }));
        form.post(url, { forceFormData: true });
    };
    const submitAccount = () =>
        accountForm.post('/company-bank-accounts/quick-store', {
            preserveScroll: true,
            onSuccess: () => {
                accountForm.reset();
                setAccountDialogOpen(false);
                router.reload({ only: ['companyBankAccounts'] });
            },
        });

    return (
        <div className="space-y-4 p-4">
            <Head title={submission.submission_number} />
            <BackButton fallback="/director/submissions" />
            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {submission.submission_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {submission.title}
                    </p>
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
                <div>Nominal PIC: {rupiah(submission.total_amount)}</div>
                <div>
                    Nominal Finance:{' '}
                    {rupiah(
                        submission.finance_detail?.validated_total_amount ??
                            submission.total_amount,
                    )}
                </div>
                <div>
                    Nominal Approval:{' '}
                    {rupiah(submission.approval_approved_amount)}
                </div>
                <div>
                    Nominal Director:{' '}
                    {submission.director_approved_amount
                        ? rupiah(submission.director_approved_amount)
                        : '-'}
                </div>
                <div>Catatan approval: {approvalReview?.notes ?? '-'}</div>
            </div>
            <SubmissionAttachments submission={submission} />
            {submission.status === 'director_review' && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        startForm.post(
                            `/director/submissions/${submission.id}/start-review`,
                        );
                    }}
                >
                    <Button disabled={startForm.processing}>
                        {startForm.processing ? 'Memulai...' : 'Mulai Review'}
                    </Button>
                </form>
            )}
            {submission.status === 'director_in_review' && (
                <div className="grid gap-4 lg:grid-cols-2">
                    <form
                        className="space-y-3 rounded-md border p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            pendingForm.post(
                                `/director/submissions/${submission.id}/approve-pending-disbursement`,
                            );
                        }}
                    >
                        <h2 className="font-semibold">Setujui - Bayar Nanti</h2>
                        <Label>Nominal disetujui</Label>
                        <MoneyInput
                            value={pendingForm.data.approved_amount}
                            onChange={(value) =>
                                pendingForm.setData('approved_amount', value)
                            }
                        />
                        <Label>Catatan Director</Label>
                        <Textarea
                            value={pendingForm.data.notes}
                            onChange={(e) =>
                                pendingForm.setData('notes', e.target.value)
                            }
                        />
                        <Button disabled={pendingForm.processing}>
                            Setujui dan Masuk Antrean
                        </Button>
                    </form>
                    <form
                        className="space-y-3 rounded-md border p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            send(
                                disburseNowForm,
                                `/director/submissions/${submission.id}/approve-and-disburse`,
                            );
                        }}
                    >
                        <h2 className="font-semibold">
                            Setujui dan Kirim Dana
                        </h2>
                        <Label>Nominal disetujui</Label>
                        <MoneyInput
                            value={disburseNowForm.data.approved_amount}
                            onChange={(value) =>
                                disburseNowForm.setData(
                                    'approved_amount',
                                    value,
                                )
                            }
                        />
                        <PaymentFields
                            form={disburseNowForm}
                            submission={submission}
                            companyBankAccounts={companyBankAccounts}
                            financeStaff={financeStaff}
                            onAddAccount={() => setAccountDialogOpen(true)}
                            onFiles={(value: File[]) =>
                                disburseNowForm.setData('attachments', value)
                            }
                            files={files}
                        />
                        <Button disabled={disburseNowForm.processing}>
                            Setujui dan Kirim Dana
                        </Button>
                    </form>
                    <form
                        className="space-y-3 rounded-md border p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            revisionForm.post(
                                `/director/submissions/${submission.id}/request-revision`,
                            );
                        }}
                    >
                        <h2 className="font-semibold">Minta Revisi</h2>
                        <Label>Catatan Revisi</Label>
                        <Textarea
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
                        className="space-y-3 rounded-md border p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            rejectForm.post(
                                `/director/submissions/${submission.id}/reject`,
                            );
                        }}
                    >
                        <h2 className="font-semibold">Tolak Pengajuan</h2>
                        <Label>Catatan Penolakan</Label>
                        <Textarea
                            value={rejectForm.data.rejection_reason}
                            onChange={(e) =>
                                rejectForm.setData(
                                    'rejection_reason',
                                    e.target.value,
                                )
                            }
                        />
                        <Button variant="outline">Tolak</Button>
                    </form>
                </div>
            )}
            {submission.status === 'pending_disbursement' && (
                <form
                    className="space-y-3 rounded-md border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        send(
                            disburseForm,
                            `/director/submissions/${submission.id}/disburse`,
                        );
                    }}
                >
                    <h2 className="font-semibold">Kirim Dana</h2>
                    <p className="text-sm text-muted-foreground">
                        Nominal pencairan:{' '}
                        {rupiah(submission.director_approved_amount)}
                    </p>
                    <PaymentFields
                        form={disburseForm}
                        submission={submission}
                        companyBankAccounts={companyBankAccounts}
                        financeStaff={financeStaff}
                        onAddAccount={() => setAccountDialogOpen(true)}
                        onFiles={(value: File[]) =>
                            disburseForm.setData('attachments', value)
                        }
                        files={files}
                    />
                    <Button disabled={disburseForm.processing}>
                        Kirim Dana
                    </Button>
                </form>
            )}
            {submission.status === 'director_revision_requested' && (
                <div className="rounded-md border p-4 text-sm">
                    Menunggu perbaikan Finance Approval. Revisi:{' '}
                    {directorReview?.revision_subject ?? '-'}
                </div>
            )}
            {submission.status === 'fund_disbursed' && (
                <div className="rounded-md border p-4 text-sm">
                    Dana sudah dikirim: {rupiah(submission.disbursed_amount)}{' '}
                    pada {formatDate(submission.disbursed_at)}
                </div>
            )}
            {submission.disbursement && (
                <DisbursementDetail disbursement={submission.disbursement} />
            )}
            <SubmissionTimeline histories={submission.status_histories ?? []} />
            <ReimbursementDetail detail={submission.reimbursement_detail} />
            <AdvanceDetail detail={submission.advance_detail} />
            <Dialog
                open={accountDialogOpen}
                onOpenChange={setAccountDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Rekening Perusahaan</DialogTitle>
                        <DialogDescription>
                            Rekening ini menjadi pilihan sumber pencairan
                            perusahaan.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        className="space-y-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitAccount();
                        }}
                    >
                        <Label>Nama bank</Label>
                        <Input
                            value={accountForm.data.bank_name}
                            onChange={(e) =>
                                accountForm.setData('bank_name', e.target.value)
                            }
                        />
                        <Label>Nomor rekening</Label>
                        <Input
                            value={accountForm.data.account_number}
                            onChange={(e) =>
                                accountForm.setData(
                                    'account_number',
                                    e.target.value,
                                )
                            }
                        />
                        <Label>Nama pada rekening</Label>
                        <Input
                            value={accountForm.data.account_holder_name}
                            onChange={(e) =>
                                accountForm.setData(
                                    'account_holder_name',
                                    e.target.value,
                                )
                            }
                        />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setAccountDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button disabled={accountForm.processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function PaymentFields({
    form,
    submission,
    companyBankAccounts,
    financeStaff,
    onAddAccount,
    onFiles,
    files,
}: any) {
    const recipientType = form.data.recipient_type;
    const selectedStaff = financeStaff.find(
        (staff: any) => staff.id === form.data.recipient_user_id,
    );
    const accounts =
        recipientType === 'finance_staff'
            ? (selectedStaff?.bank_accounts ?? [])
            : recipientType === 'pic_kdkmp'
              ? (submission.submitter?.bank_accounts ?? [])
              : recipientType === 'cooperative'
                ? (submission.cooperative?.bank_accounts ?? [])
                : [];
    const selectRecipient = (type: string) =>
        form.setData({
            ...form.data,
            recipient_type: type,
            recipient_user_id:
                type === 'pic_kdkmp' ? submission.submitted_by : '',
            recipient_cooperative_id:
                type === 'cooperative' ? submission.cooperative_id : '',
            destination_bank_account_id: '',
        });
    return (
        <>
            <Label>Waktu transfer</Label>
            <Input
                type="datetime-local"
                value={form.data.transferred_at}
                onChange={(e) => form.setData('transferred_at', e.target.value)}
            />
            <Label>Metode pembayaran</Label>
            <Select
                value={form.data.payment_method}
                onValueChange={(value) => form.setData('payment_method', value)}
            >
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="virtual_account">
                        Virtual Account
                    </SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                </SelectContent>
            </Select>
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>Rekening sumber perusahaan</Label>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={onAddAccount}
                    >
                        Tambah
                    </Button>
                </div>
                <Select
                    value={form.data.source_company_bank_account_id}
                    onValueChange={(value) =>
                        form.setData('source_company_bank_account_id', value)
                    }
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Pilih rekening sumber" />
                    </SelectTrigger>
                    <SelectContent>
                        {companyBankAccounts.map((account: any) => (
                            <SelectItem key={account.id} value={account.id}>
                                {account.bank_name} -{' '}
                                {account.account_holder_name} -{' '}
                                {account.account_number}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <Label>Jenis penerima</Label>
            <Select value={recipientType} onValueChange={selectRecipient}>
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="finance_staff">Finance Staff</SelectItem>
                    <SelectItem value="pic_kdkmp">PIC KDKMP</SelectItem>
                    <SelectItem value="cooperative">Koperasi</SelectItem>
                    <SelectItem value="other">Penerima Lain</SelectItem>
                </SelectContent>
            </Select>
            {recipientType === 'finance_staff' && (
                <>
                    <Label>Penerima</Label>
                    <Select
                        value={form.data.recipient_user_id}
                        onValueChange={(value) =>
                            form.setData({
                                ...form.data,
                                recipient_user_id: value,
                                destination_bank_account_id: '',
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih Finance Staff" />
                        </SelectTrigger>
                        <SelectContent>
                            {financeStaff.map((staff: any) => (
                                <SelectItem key={staff.id} value={staff.id}>
                                    {staff.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </>
            )}
            {recipientType === 'pic_kdkmp' && (
                <p className="rounded-md border p-3 text-sm">
                    Penerima: {submission.submitter?.name}
                </p>
            )}
            {recipientType === 'cooperative' && (
                <p className="rounded-md border p-3 text-sm">
                    Penerima: {submission.cooperative?.name}
                </p>
            )}
            {recipientType !== 'other' && (
                <>
                    <Label>Rekening tujuan</Label>
                    <Select
                        value={form.data.destination_bank_account_id}
                        onValueChange={(value) =>
                            form.setData('destination_bank_account_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih rekening tujuan" />
                        </SelectTrigger>
                        <SelectContent>
                            {accounts.map((account: any) => (
                                <SelectItem key={account.id} value={account.id}>
                                    {account.bank_name} -{' '}
                                    {account.account_holder_name} -{' '}
                                    {account.account_number}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </>
            )}
            {recipientType === 'other' && (
                <>
                    <Label>Nama penerima</Label>
                    <Input
                        value={form.data.recipient_name}
                        onChange={(e) =>
                            form.setData('recipient_name', e.target.value)
                        }
                    />
                    <Label>Nama bank tujuan</Label>
                    <Input
                        value={form.data.destination_bank_name}
                        onChange={(e) =>
                            form.setData(
                                'destination_bank_name',
                                e.target.value,
                            )
                        }
                    />
                    <Label>Nomor rekening tujuan</Label>
                    <Input
                        value={form.data.destination_account_number}
                        onChange={(e) =>
                            form.setData(
                                'destination_account_number',
                                e.target.value,
                            )
                        }
                    />
                    <Label>Nama pemilik rekening</Label>
                    <Input
                        value={form.data.destination_account_holder}
                        onChange={(e) =>
                            form.setData(
                                'destination_account_holder',
                                e.target.value,
                            )
                        }
                    />
                </>
            )}
            <Label>Nomor referensi</Label>
            <Input
                value={form.data.transaction_reference}
                onChange={(e) =>
                    form.setData('transaction_reference', e.target.value)
                }
            />
            <Label>Catatan</Label>
            <Textarea
                value={form.data.notes}
                onChange={(e) => form.setData('notes', e.target.value)}
            />
            <Label>Bukti transfer</Label>
            <Input
                type="file"
                multiple
                accept=".pdf,.jpg,.jpeg,.png,.webp"
                onChange={(e) => onFiles(files(e))}
            />
        </>
    );
}

function DisbursementDetail({ disbursement }: any) {
    return (
        <div className="grid gap-2 rounded-md border p-4 text-sm md:grid-cols-2">
            <h2 className="font-semibold md:col-span-2">Detail Pencairan</h2>
            <div>Nomor: {disbursement.disbursement_number}</div>
            <div>Penerima: {disbursement.recipient_name_snapshot}</div>
            <div>
                Sumber: {disbursement.source_bank_name} -{' '}
                {disbursement.source_account_number_masked_snapshot ?? '-'}
            </div>
            <div>
                Tujuan: {disbursement.destination_bank_snapshot} -{' '}
                {disbursement.destination_account_number_masked ?? '-'}
            </div>
            <div>Status distribusi: {disbursement.distribution_status}</div>
            <div>
                Bukti:{' '}
                {(disbursement.attachments ?? []).map((attachment: any) => (
                    <a
                        key={attachment.id}
                        className="mr-3 underline"
                        href={`/director/disbursement-attachments/${attachment.id}/download`}
                    >
                        {attachment.original_name}
                    </a>
                ))}
            </div>
        </div>
    );
}
