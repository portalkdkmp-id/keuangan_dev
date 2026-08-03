import { Link } from '@inertiajs/react';
import { FilePlus2, ReceiptText } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export function CreateSubmissionDialog({
    triggerLabel = 'Tambah Pengajuan',
}: {
    triggerLabel?: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <FilePlus2 />
                    {triggerLabel}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Pilih jenis pengajuan</DialogTitle>
                    <DialogDescription>
                        Pilih alur yang sesuai dengan kebutuhan pengajuan Anda.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-3 py-2">
                    <Button
                        asChild
                        variant="outline"
                        className="h-auto justify-start gap-3 p-4 text-left"
                    >
                        <Link href="/submissions/create">
                            <FilePlus2 className="size-5" />
                            <span>
                                <span className="block font-semibold">
                                    Pengajuan Dana
                                </span>
                                <span className="block text-xs font-normal text-muted-foreground">
                                    Dana yang belum dibayarkan dan akan
                                    digunakan.
                                </span>
                            </span>
                        </Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="h-auto justify-start gap-3 p-4 text-left"
                    >
                        <Link href="/reimbursements/create">
                            <ReceiptText className="size-5" />
                            <span>
                                <span className="block font-semibold">
                                    Pengajuan Reimbursement
                                </span>
                                <span className="block text-xs font-normal text-muted-foreground">
                                    Penggantian biaya yang sudah dibayarkan.
                                </span>
                            </span>
                        </Link>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
