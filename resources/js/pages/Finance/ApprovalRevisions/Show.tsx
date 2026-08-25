import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
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
import { SubmissionAttachments } from '@/components/Submissions/SubmissionAttachments';
import { SubmissionItemsEditor } from '@/components/Submissions/SubmissionItemsEditor';

export default function FinanceApprovalRevisionShow({
    submission,
    requestCategories,
    requestTypes,
}: any) {
    const review = submission.approval_reviews?.[0];
    const [confirm, setConfirm] = useState<'approval' | 'pic' | null>(null);
    const form = useForm({
        title: submission.title ?? '',
        submission_request_category_id:
            submission.submission_request_category_id ?? '',
        submission_request_type_id: submission.submission_request_type_id ?? '',
        amount: submission.total_amount ?? '',
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
        needed_date: String(submission.needed_date ?? '').slice(0, 10),
        notes: submission.notes ?? '',
        finance_notes: submission.finance_detail?.finance_notes ?? '',
        attachments: [] as File[],
    });
    const resubmitForm = useForm({ change_summary: '', notes: '' });
    const picForm = useForm({ message: '' });
    const availableRequestTypes = requestTypes.filter(
        (type: any) =>
            !type.submission_request_category_id ||
            type.submission_request_category_id ===
                form.data.submission_request_category_id,
    );
    const save = (onSuccess?: () => void) =>
        form.post(`/finance/approval-revisions/${submission.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess,
        });

    return (
        <div className="space-y-4 p-4">
            <Head title={submission.submission_number} />
            <div>
                <h1 className="text-2xl font-semibold">Revisi Approval</h1>
                <p className="text-sm text-muted-foreground">
                    {submission.submission_number} · {submission.title}
                </p>
            </div>
            <div className="border-l-4 border-amber-500 bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100">
                <h2 className="font-semibold">{review?.revision_subject}</h2>
                <p>{review?.revision_message}</p>
            </div>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.transform((data: any) => ({
                        ...data,
                        _method: 'put',
                    }));
                    save();
                }}
                className="space-y-4 rounded-md border p-4"
            >
                <div className="grid gap-3 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Label>Judul</Label>
                        <Input
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                        />
                    </div>
                    <div>
                        <Label>Kategori</Label>
                        <Select
                            value={
                                form.data.submission_request_category_id ||
                                undefined
                            }
                            onValueChange={(value) =>
                                form.setData((data: any) => ({
                                    ...data,
                                    submission_request_category_id: value,
                                    items: data.items.map((item: any) => {
                                        const type = requestTypes.find(
                                            (candidate: any) =>
                                                candidate.id ===
                                                item.request_type_id,
                                        );
                                        return type?.submission_request_category_id &&
                                            type.submission_request_category_id !==
                                                value
                                            ? {
                                                  ...item,
                                                  request_type_id: '',
                                                  other_type_name: '',
                                              }
                                            : item;
                                    }),
                                }))
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
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
                        <Label>Tanggal dibutuhkan</Label>
                        <Input
                            type="date"
                            value={form.data.needed_date}
                            onChange={(event) =>
                                form.setData('needed_date', event.target.value)
                            }
                        />
                    </div>
                </div>
                <SubmissionItemsEditor
                    form={form}
                    requestTypes={availableRequestTypes}
                />
                <div className="grid gap-3 md:grid-cols-2">
                    <div>
                        <Label>Catatan PIC</Label>
                        <Textarea
                            className="min-h-24"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                        />
                    </div>
                    <div>
                        <Label>Catatan Finance</Label>
                        <Textarea
                            className="min-h-24"
                            value={form.data.finance_notes}
                            onChange={(event) =>
                                form.setData(
                                    'finance_notes',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                    <div className="md:col-span-2">
                        <Label>Attachment baru</Label>
                        <Input
                            type="file"
                            multiple
                            onChange={(event) =>
                                form.setData(
                                    'attachments',
                                    Array.from(event.target.files ?? []),
                                )
                            }
                        />
                    </div>
                </div>
                <Button disabled={form.processing}>Simpan Perbaikan</Button>
            </form>
            <SubmissionAttachments submission={submission} editable />
            <div className="space-y-3 rounded-md border p-4">
                <Label>Ringkasan perubahan</Label>
                <Textarea
                    value={resubmitForm.data.change_summary}
                    onChange={(event) =>
                        resubmitForm.setData(
                            'change_summary',
                            event.target.value,
                        )
                    }
                />
                <Label>Catatan ke Approver</Label>
                <Textarea
                    value={resubmitForm.data.notes}
                    onChange={(event) =>
                        resubmitForm.setData('notes', event.target.value)
                    }
                />
                <div className="flex flex-wrap gap-2">
                    <Button onClick={() => setConfirm('approval')}>
                        Kirim Ulang ke Approval
                    </Button>
                    <Button variant="outline" onClick={() => setConfirm('pic')}>
                        Teruskan Revisi ke PIC
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.visit('/finance/approval-revisions')
                        }
                    >
                        Batal
                    </Button>
                </div>
            </div>
            <Dialog
                open={confirm === 'approval'}
                onOpenChange={(open) => !open && setConfirm(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kirim ulang ke Approval?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pastikan seluruh perbaikan dan attachment sudah benar.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button
                            disabled={resubmitForm.processing}
                            onClick={() =>
                                resubmitForm.post(
                                    `/finance/approval-revisions/${submission.id}/resubmit`,
                                )
                            }
                        >
                            Ya, Kirim
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog
                open={confirm === 'pic'}
                onOpenChange={(open) => !open && setConfirm(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Teruskan revisi ke PIC?</DialogTitle>
                    </DialogHeader>
                    <Label>Catatan untuk PIC</Label>
                    <Textarea
                        className="min-h-28"
                        value={picForm.data.message}
                        onChange={(event) =>
                            picForm.setData('message', event.target.value)
                        }
                    />
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button
                            disabled={
                                picForm.processing ||
                                !picForm.data.message.trim()
                            }
                            onClick={() =>
                                picForm.post(
                                    `/finance/approval-revisions/${submission.id}/request-pic-revision`,
                                )
                            }
                        >
                            Kirim ke PIC
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
