import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

export default function Index({ directDisbursements = [], distributions = [], confirmations = { data: [] } }: any) {
    const [source, setSource] = useState<any>(null);
    const form = useForm({ received_at: '', notes: '' });
    const pending = [
        ...directDisbursements.map((item: any) => ({ ...item, sourceType: 'disbursements' })),
        ...distributions.map((item: any) => ({ ...item, sourceType: 'distributions' })),
    ];
    const confirm = () => {
        if (!source) {
            return;
        }

        form.post(`/fund-receipts/${source.sourceType}/${source.id}`, {
            onSuccess: () => {
                setSource(null);
                form.reset();
            },
        });
    };
    const proofUrl = (item: any, attachment: any) => item.sourceType === 'disbursements'
        ? `/director/disbursement-attachments/${attachment.id}/download`
        : `/fund-distribution-attachments/${attachment.id}/download`;

    return <div className="space-y-6 p-4"><Head title="Konfirmasi Penerimaan" />
        <div><h1 className="text-2xl font-semibold">Konfirmasi Penerimaan Dana</h1><p className="text-sm text-muted-foreground">Konfirmasi hanya setelah dana benar-benar masuk ke rekening tujuan.</p></div>
        <div className="grid gap-3 md:grid-cols-2">{pending.length ? pending.map((item: any) => <div key={`${item.sourceType}-${item.id}`} className="space-y-3 rounded-md border p-4"><h2 className="font-semibold">Dana Telah Dikirim</h2><div className="text-sm">{item.submission?.submission_number}</div><div className="text-2xl font-semibold">{rupiah(item.amount)}</div><div className="text-sm text-muted-foreground">Tujuan: {item.destination_bank_snapshot ?? item.destination_bank_name_snapshot} · Referensi: {item.transaction_reference ?? '-'}</div><div className="text-sm">Bukti: {(item.attachments ?? []).map((attachment: any) => <a key={attachment.id} className="mr-2 underline" href={proofUrl(item, attachment)}>{attachment.original_name}</a>)}</div><Button onClick={() => setSource(item)}>Konfirmasi Dana Diterima</Button></div>) : <div className="rounded-md border border-dashed p-8 text-center text-muted-foreground md:col-span-2">Tidak ada dana yang menunggu konfirmasi.</div>}</div>
        <div className="overflow-x-auto rounded-md border"><Table><TableHeader><TableRow><TableHead>Pengajuan</TableHead><TableHead>Nominal</TableHead><TableHead>Waktu diterima</TableHead></TableRow></TableHeader><TableBody>{confirmations.data.map((item: any) => <TableRow key={item.id}><TableCell>{item.submission?.submission_number}</TableCell><TableCell>{rupiah(item.amount)}</TableCell><TableCell>{item.received_at}</TableCell></TableRow>)}</TableBody></Table></div>
        <Dialog open={!!source} onOpenChange={(open) => !open && setSource(null)}><DialogContent><DialogHeader><DialogTitle>Konfirmasi Dana Diterima</DialogTitle></DialogHeader><Label>Tanggal dan waktu diterima</Label><Input type="datetime-local" value={form.data.received_at} onChange={(event) => form.setData('received_at', event.target.value)} /><Label>Catatan</Label><Textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} /><DialogFooter><Button type="button" variant="outline" onClick={() => setSource(null)}>Batal</Button><Button type="button" disabled={!source || form.processing} onClick={confirm}>Konfirmasi</Button></DialogFooter></DialogContent></Dialog>
    </div>;
}
