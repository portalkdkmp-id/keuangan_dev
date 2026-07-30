import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function BankAccountsIndex({ accounts }: any) {
    return <div className="space-y-4 p-4"><Head title="Rekening" />
        <div className="flex items-center justify-between"><h1 className="text-2xl font-semibold">Rekening</h1><Button asChild><Link href="/bank-accounts/create">Tambah</Link></Button></div>
        <div className="overflow-hidden rounded-md border"><Table><TableHeader><TableRow><TableHead>Bank</TableHead><TableHead>Nama rekening</TableHead><TableHead>Nomor</TableHead><TableHead>Status</TableHead><TableHead /></TableRow></TableHeader><TableBody>{accounts.data.map((account: any) => <TableRow key={account.id}><TableCell>{account.bank_name}</TableCell><TableCell>{account.account_holder_name}</TableCell><TableCell>{account.account_number}</TableCell><TableCell>{account.is_primary ? 'Utama' : ''}</TableCell><TableCell className="text-right"><Button size="sm" variant="outline" asChild><Link href={`/bank-accounts/${account.id}/edit`}>Edit</Link></Button><Button size="sm" variant="outline" onClick={() => router.delete(`/bank-accounts/${account.id}`)}>Hapus</Button></TableCell></TableRow>)}</TableBody></Table></div>
    </div>;
}
