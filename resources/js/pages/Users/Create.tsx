import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function UsersCreate({ roles }: { roles: string[] }) {
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        role: roles[0] ?? '',
        is_active: true as boolean,
    });
    return (
        <UserForm
            title="Create User"
            roles={roles}
            form={form}
            onSubmit={() => form.post('/users')}
        />
    );
}

export function UserForm({ title, roles, form, onSubmit }: any) {
    return (
        <div className="max-w-2xl space-y-4 p-4">
            <Head title={title} />
            <h1 className="text-2xl font-semibold">{title}</h1>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    onSubmit();
                }}
                className="space-y-4"
            >
                <div>
                    <Label>Name</Label>
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </div>
                <div>
                    <Label>Email</Label>
                    <Input
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                </div>
                <div>
                    <Label>Phone</Label>
                    <Input
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.target.value)}
                    />
                </div>
                <div>
                    <Label>Password</Label>
                    <Input
                        type="password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                    />
                </div>
                <div>
                    <Label>Role</Label>
                    <Select
                        value={form.data.role}
                        onValueChange={(value) => form.setData('role', value)}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select role" />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((role: string) => (
                                <SelectItem key={role} value={role}>
                                    {role}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <label className="flex gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.is_active}
                        onChange={(e) =>
                            form.setData('is_active', e.target.checked)
                        }
                    />{' '}
                    Active
                </label>
                <div className="flex gap-2">
                    <Button type="submit">Save</Button>
                    <Button variant="outline" asChild>
                        <Link href="/users">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
