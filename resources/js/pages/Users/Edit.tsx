import { useForm } from '@inertiajs/react';
import { UserForm } from './Create';

export default function UsersEdit({ managedUser, roles }: any) {
    const form = useForm({ name: managedUser.name, email: managedUser.email, phone: managedUser.phone ?? '', password: '', role: managedUser.roles?.[0]?.name ?? roles[0], is_active: !!managedUser.is_active });
    return <UserForm title="Edit User" roles={roles} form={form} onSubmit={() => form.put(`/users/${managedUser.id}`)} />;
}
