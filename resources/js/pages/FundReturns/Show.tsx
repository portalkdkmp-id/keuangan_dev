import { Head, Link, router } from '@inertiajs/react';
import { BackButton } from '@/components/back-button';
import { FundReturnSummary } from '@/components/FundReturns/FundReturnSummary';
import { Button } from '@/components/ui/button';
export default function Show({ fundReturn }: any) {
    const editable = ['draft', 'revision_requested'].includes(
        fundReturn.status,
    );
    return (
        <div className="mx-auto max-w-3xl space-y-5 p-4">
            <Head title={fundReturn.return_number} />
            <BackButton fallback="/fund-returns" />
            <FundReturnSummary fundReturn={fundReturn} />
            {editable && (
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={`/fund-returns/${fundReturn.id}/edit`}>
                            Edit
                        </Link>
                    </Button>
                    <Button
                        onClick={() =>
                            router.post(`/fund-returns/${fundReturn.id}/submit`)
                        }
                    >
                        Ajukan Verifikasi
                    </Button>
                </div>
            )}
        </div>
    );
}
