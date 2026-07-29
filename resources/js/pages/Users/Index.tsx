import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function UsersIndex({ users, roles, filters }: any) {
    const [role, setRole] = useState(filters.role || 'all');
    const [status, setStatus] = useState(filters.is_active || 'all');
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget));
        router.get('/users', data, { preserveState: true });
    };
    return (
        <div className="space-y-4 p-4">
            <Head title="Users" />
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Users</h1>
                <Button asChild>
                    <Link href="/users/create">Create</Link>
                </Button>
            </div>
            <form onSubmit={submit} className="grid gap-2 md:grid-cols-4">
                <Input
                    name="search"
                    defaultValue={filters.search ?? ''}
                    placeholder="Search name or email"
                />
                <input
                    type="hidden"
                    name="role"
                    value={role === 'all' ? '' : role}
                />
                <Select value={role} onValueChange={setRole}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All roles</SelectItem>
                        {roles.map((r: string) => (
                            <SelectItem key={r} value={r}>
                                {r}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <input
                    type="hidden"
                    name="is_active"
                    value={status === 'all' ? '' : status}
                />
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="All status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All status</SelectItem>
                        <SelectItem value="1">Active</SelectItem>
                        <SelectItem value="0">Inactive</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit">Filter</Button>
            </form>
            <div className="overflow-hidden rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted">
                            <th className="p-3 text-left">Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user: any) => (
                            <tr key={user.id} className="border-b">
                                <td className="p-3">{user.name}</td>
                                <td>{user.email}</td>
                                <td>
                                    {user.roles
                                        ?.map((r: any) => r.name)
                                        .join(', ')}
                                </td>
                                <td>
                                    {user.is_active ? 'Active' : 'Inactive'}
                                </td>
                                <td className="p-3 text-right">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/users/${user.id}/edit`}>
                                            Edit
                                        </Link>
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
