import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function CooperativesShow({ cooperative, availablePics }: any) {
    const form = useForm({
        user_id: availablePics[0]?.id ?? '',
        is_primary: false as boolean,
    });
    return (
        <div className="space-y-4 p-4">
            <Head title={cooperative.name} />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {cooperative.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {cooperative.nik}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Link href={`/cooperatives/${cooperative.id}/edit`}>
                        Edit
                    </Link>
                </Button>
            </div>
            <div className="rounded-md border p-4 text-sm">
                {cooperative.village?.name}, {cooperative.district?.name},{' '}
                {cooperative.city?.name}, {cooperative.province?.name}
            </div>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(`/cooperatives/${cooperative.id}/pics`);
                }}
                className="flex gap-2"
            >
                <Select
                    value={form.data.user_id || undefined}
                    onValueChange={(value) => form.setData('user_id', value)}
                >
                    <SelectTrigger className="w-64">
                        <SelectValue placeholder="Select PIC" />
                    </SelectTrigger>
                    <SelectContent>
                        {availablePics.map((pic: any) => (
                            <SelectItem key={pic.id} value={pic.id}>
                                {pic.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.is_primary}
                        onChange={(e) =>
                            form.setData('is_primary', e.target.checked)
                        }
                    />{' '}
                    Primary
                </label>
                <Button type="submit">Assign PIC</Button>
            </form>
            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <tbody>
                        {cooperative.pics.map((pic: any) => (
                            <tr key={pic.id} className="border-b">
                                <td className="p-3">{pic.name}</td>
                                <td>
                                    {pic.pivot?.is_primary
                                        ? 'Primary'
                                        : 'Backup'}
                                </td>
                                <td className="p-3 text-right">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.patch(
                                                `/cooperatives/${cooperative.id}/pics/${pic.id}/primary`,
                                            )
                                        }
                                    >
                                        Make Primary
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
