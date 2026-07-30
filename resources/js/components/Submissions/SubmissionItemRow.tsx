import { TableCell, TableRow } from '@/components/ui/table';

export function SubmissionItemRow({ item }: { item: any }) {
    return <TableRow><TableCell>{item.category_name}</TableCell><TableCell>{item.description}</TableCell><TableCell>{item.quantity} {item.unit}</TableCell><TableCell>{item.unit_price}</TableCell><TableCell>{item.subtotal}</TableCell></TableRow>;
}
