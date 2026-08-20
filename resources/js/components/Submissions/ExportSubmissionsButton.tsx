import { usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';

export function ExportSubmissionsButton() {
    const permissions = usePage<SharedData>().props.auth.permissions ?? [];
    if (!permissions.includes('submissions.export')) return null;

    return (
        <Button asChild variant="outline">
            <a href="/export-laporan">
                <Download className="size-4" /> Export Laporan
            </a>
        </Button>
    );
}
