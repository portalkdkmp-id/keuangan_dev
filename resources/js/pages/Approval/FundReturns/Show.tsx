import { Head, useForm } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { FundReturnSummary } from '@/components/FundReturns/FundReturnSummary';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
export default function Show({ fundReturn }: any) {
    const f = useForm({ notes: '' });
    return (
        <div className="mx-auto max-w-3xl space-y-5 p-4">
            <Head title={fundReturn.return_number} />
            <BackButton fallback="/approval/fund-returns" />
            <FundReturnSummary fundReturn={fundReturn} />
            {fundReturn.status === 'finance_verified' && (
                <div className="space-y-3">
                    <Textarea
                        placeholder="Catatan approval (opsional)"
                        value={f.data.notes}
                        onChange={(e) => f.setData('notes', e.target.value)}
                    />
                    <Button
                        onClick={() =>
                            f.post(
                                `/approval/fund-returns/${fundReturn.id}/approve`,
                            )
                        }
                    >
                        Setujui dan Tutup
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() =>
                            f.post(
                                `/approval/fund-returns/${fundReturn.id}/reject`,
                            )
                        }
                    >
                        Tolak
                    </Button>
                </div>
            )}
        </div>
    );
}
