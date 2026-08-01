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
        'submissions.revise', 'submissions.resubmit',
        'submission-masters.view', 'submission-masters.create', 'submission-masters.update', 'submission-masters.delete',
        'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
        'finance-submissions.view', 'finance-submissions.review', 'finance-submissions.update',
        'finance-submissions.request-revision', 'finance-submissions.validate', 'finance-submissions.forward-approval',
        'finance-submissions.view-approval-revision', 'finance-submissions.update-approval-revision', 'finance-submissions.resubmit-approval',
        'approval-submissions.view', 'approval-submissions.review', 'approval-submissions.approve',
        'approval-submissions.reject', 'approval-submissions.request-revision',
        'director-submissions.view',
        'finance-monitoring.view', 'approval-monitoring.view', 'global-monitoring.view',
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
                    'submissions.revise', 'submissions.resubmit',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
                    'notifications.view', 'notifications.mark-read',
                ],
                'finance_staff' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'finance-submissions.view', 'finance-submissions.review', 'finance-submissions.update',
                    'finance-submissions.request-revision', 'finance-submissions.validate', 'finance-submissions.forward-approval',
                    'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete', 'submissions.submit',
                    'finance-submissions.view-approval-revision', 'finance-submissions.update-approval-revision', 'finance-submissions.resubmit-approval',
                    'finance-monitoring.view',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
                    'notifications.view', 'notifications.mark-read',
                ],
                'finance_approver' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'approval-submissions.view', 'approval-submissions.review', 'approval-submissions.approve',
                    'approval-submissions.reject', 'approval-submissions.request-revision',
                    'approval-monitoring.view',
                    'notifications.view', 'notifications.mark-read',
                ],
                'finance_director' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'director-submissions.view',
                    'notifications.view', 'notifications.mark-read',
                ],
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
