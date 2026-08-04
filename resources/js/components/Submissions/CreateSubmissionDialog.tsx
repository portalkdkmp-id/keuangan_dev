import { Link, usePage } from '@inertiajs/react';
import { Banknote, FilePlus2, ReceiptText } from 'lucide-react';
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
import type { SharedData } from '@/types';

export function CreateSubmissionDialog({
    triggerLabel = 'Tambah Pengajuan',
}: {
    triggerLabel?: string;
}) {
    const [open, setOpen] = useState(false);
    const permissions = usePage<SharedData>().props.auth.permissions ?? [];

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
                    {permissions.includes('advances.create') && (
                        <Button
                            asChild
                            variant="outline"
                            className="h-auto justify-start gap-3 p-4 text-left"
                        >
                            <Link href="/advances/create">
                                <Banknote className="size-5" />
                                <span>
                                    <span className="block font-semibold">
                                        Uang Panjar
                                    </span>
                                    <span className="block text-xs font-normal text-muted-foreground">
                                        Dana sementara sebelum transaksi
                                        dilakukan.
                                    </span>
                                </span>
                            </Link>
                        </Button>
                    )}
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
