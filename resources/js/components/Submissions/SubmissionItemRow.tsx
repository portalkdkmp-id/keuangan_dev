export function SubmissionItemRow({ item }: { item: any }) {
    return <tr className="border-b"><td className="p-3">{item.category_name}</td><td>{item.description}</td><td>{item.quantity} {item.unit}</td><td>{item.unit_price}</td><td>{item.subtotal}</td></tr>;
}
