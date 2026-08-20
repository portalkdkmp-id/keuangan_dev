<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('submissions.export', 'web');

        Role::query()->whereIn('name', ['super_admin', 'pic_kdkmp', 'finance_staff', 'finance_approver', 'finance_director'])
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()->whereIn('name', ['pic_kdkmp', 'finance_director'])
            ->each(fn (Role $role) => $role->revokePermissionTo('submissions.export'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
