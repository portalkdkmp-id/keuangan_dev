import { Head, router, useForm } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { FundReturnSummary } from '@/components/FundReturns/FundReturnSummary';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
export default function Show({ fundReturn }: any) {
    const f = useForm({ notes: '' });
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
            {fundReturn.status === 'finance_review' && (
                <div className="space-y-3">
                    <Textarea
                        placeholder="Catatan review atau revisi"
                        value={f.data.notes}
                        onChange={(e) => f.setData('notes', e.target.value)}
                    />
                    <div className="flex gap-2">
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
                            onClick={() =>
                                f.post(
                                    `/finance/fund-returns/${fundReturn.id}/verify`,
                                )
                            }
                        >
                            Verifikasi
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
                    </div>
                </div>
            )}
        </div>
    );
}
