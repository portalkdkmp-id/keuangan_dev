<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const ROLES = ['super_admin', 'pic_kdkmp', 'finance_staff', 'finance_approver', 'finance_director'];

    public const PERMISSIONS = [
        'dashboard.view',
        'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-role',
        'regions.view', 'regions.import',
        'cooperatives.view', 'cooperatives.create', 'cooperatives.update', 'cooperatives.delete', 'cooperatives.assign-pic',
        'profile.view', 'profile.update',
        'audit-logs.view',
        'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete', 'submissions.submit',
        'finance-submissions.view', 'finance-submissions.review',
        'notifications.view', 'notifications.mark-read',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(match ($roleName) {
                'super_admin' => self::PERMISSIONS,
                'pic_kdkmp' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete', 'submissions.submit',
                    'notifications.view', 'notifications.mark-read',
                ],
                'finance_staff' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'finance-submissions.view', 'finance-submissions.review',
                    'notifications.view', 'notifications.mark-read',
                ],
                'finance_approver', 'finance_director' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'notifications.view',
                ],
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
