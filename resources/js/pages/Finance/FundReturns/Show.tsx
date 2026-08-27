import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { BackButton } from '@/components/back-button';
import { FundReturnSummary } from '@/components/FundReturns/FundReturnSummary';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
export default function Show({ fundReturn }: any) {
    const f = useForm({ notes: '' });
    const [confirmApproval, setConfirmApproval] = useState(false);
    const isFinanceAction = ['submitted', 'finance_review'].includes(
        fundReturn.status,
    );

    const approve = () => {
        setConfirmApproval(false);
        f.post(`/finance/fund-returns/${fundReturn.id}/verify`);
    };

    return (
        <div className="mx-auto max-w-3xl space-y-5 p-4">
            <Head title={fundReturn.return_number} />
            <BackButton fallback="/finance/fund-returns" />
            <FundReturnSummary fundReturn={fundReturn} />
            {fundReturn.status === 'submitted' && (
                <Button
                    onClick={() =>
                        router.post(
                            `/finance/fund-returns/${fundReturn.id}/start-review`,
                        )
                    }
                >
                    Mulai Review
                </Button>
            )}
            {isFinanceAction && (
                <div className="space-y-3">
                    <Textarea
                        placeholder="Catatan review (opsional)"
                        value={f.data.notes}
                        onChange={(e) => f.setData('notes', e.target.value)}
                    />
                    <div className="flex flex-wrap gap-2">
                        {fundReturn.status === 'finance_review' && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        f.post(
                                            `/finance/fund-returns/${fundReturn.id}/request-revision`,
                                        )
                                    }
                                >
                                    Minta Revisi
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={() =>
                                        f.post(
                                            `/finance/fund-returns/${fundReturn.id}/reject`,
                                        )
                                    }
                                >
                                    Tolak
                                </Button>
                            </>
                        )}
                        <Button
                            onClick={() => setConfirmApproval(true)}
                            disabled={f.processing}
                        >
                            Setujui &amp; Ajukan ke Approval
                        </Button>
                    </div>
                </div>
            )}
            <Dialog open={confirmApproval} onOpenChange={setConfirmApproval}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Setujui pengembalian dana?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pastikan nominal, rekening tujuan, dan bukti
                        pengembalian sudah sesuai. Data akan diteruskan ke
                        Finance Approval.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button onClick={approve} disabled={f.processing}>
                            Ya, Setujui
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
