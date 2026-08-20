import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function ExportSingleSubmissionButton({ id }: { id: string }) {
    return (
        <Button asChild variant="outline">
            <a href={`/export-laporan/${id}/download`}>
                <Download className="size-4" /> Export Pengajuan
            </a>
        </Button>
    );
}
