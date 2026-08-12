import { rupiah } from '@/components/Submissions/SubmissionSummary';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export function SubmissionItemsTable({ items = [] }: { items?: any[] }) {
    return (
        <div className="overflow-x-auto rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama Item</TableHead>
                        <TableHead>Jenis Item</TableHead>
                        <TableHead className="text-right">Nominal</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {items.map((item) => (
                        <TableRow key={item.id}>
                            <TableCell className="font-medium">
                                {item.description}
                            </TableCell>
                            <TableCell>
                                {item.other_type_name ||
                                    item.request_type_name ||
                                    item.category_name ||
                                    '-'}
                            </TableCell>
                            <TableCell className="text-right">
                                {rupiah(item.subtotal ?? item.unit_price)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
