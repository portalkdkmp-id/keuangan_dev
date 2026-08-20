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
        'pics.view', 'pics.create', 'pics.update', 'pics.delete', 'pics.assign-cooperatives',
        'regions.view', 'regions.import',
        'cooperatives.view', 'cooperatives.create', 'cooperatives.update', 'cooperatives.delete', 'cooperatives.assign-pic', 'koperasi.assign-pic',
        'profile.view', 'profile.update',
        'audit-logs.view',
        'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete', 'submissions.submit',
        'submissions.export',
        'submissions.revise', 'submissions.resubmit',
        'submission-masters.view', 'submission-masters.create', 'submission-masters.update', 'submission-masters.delete',
        'submission-categories.view', 'submission-categories.create', 'submission-categories.update', 'submission-categories.delete',
        'submission-types.view', 'submission-types.create', 'submission-types.update', 'submission-types.delete',
        'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
        'company-bank-accounts.view', 'company-bank-accounts.create', 'company-bank-accounts.update', 'company-bank-accounts.delete', 'company-bank-accounts.set-primary',
        'cooperative-bank-accounts.view', 'cooperative-bank-accounts.create', 'cooperative-bank-accounts.update', 'cooperative-bank-accounts.delete', 'cooperative-bank-accounts.set-primary',
        'fund-distributions.view', 'fund-distributions.create', 'fund-distributions.download-proof', 'fund-distributions.monitor',
        'fund-receipts.view', 'fund-receipts.confirm',
        'accountability-reports.view', 'accountability-reports.create', 'accountability-reports.update', 'accountability-reports.submit',
        'accountability-reports.review', 'accountability-reports.request-revision', 'accountability-reports.verify', 'accountability-reports.approve', 'accountability-reports.download-attachment',
        'fund-monitoring.view',
        'finance-submissions.view', 'finance-submissions.review', 'finance-submissions.update',
        'finance-submissions.request-revision', 'finance-submissions.validate', 'finance-submissions.forward-approval',
        'finance-submissions.view-approval-revision', 'finance-submissions.update-approval-revision', 'finance-submissions.resubmit-approval',
        'approval-submissions.view', 'approval-submissions.review', 'approval-submissions.approve',
        'approval-submissions.reject', 'approval-submissions.request-revision',
        'approval-submissions.view-director-revision', 'approval-submissions.update-director-revision', 'approval-submissions.resubmit-director',
        'director-submissions.view', 'director-submissions.review', 'director-submissions.approve',
        'director-submissions.disburse', 'director-submissions.reject', 'director-submissions.request-revision',
        'director-submissions.view-all',
        'disbursements.view', 'disbursements.create', 'disbursements.download-proof',
        'finance-monitoring.view', 'approval-monitoring.view', 'director-monitoring.view', 'director-disbursements.view', 'global-monitoring.view',
        'notifications.view', 'notifications.mark-read',
        'reimbursements.view', 'reimbursements.create', 'reimbursements.update', 'reimbursements.submit', 'reimbursements.review', 'reimbursements.request-revision', 'reimbursements.validate', 'reimbursements.approve', 'reimbursements.reject', 'reimbursements.download-attachment',
        'fund-returns.view', 'fund-returns.create', 'fund-returns.update', 'fund-returns.submit', 'fund-returns.review', 'fund-returns.request-revision', 'fund-returns.verify', 'fund-returns.approve', 'fund-returns.reject', 'fund-returns.download-attachment',
        'reimbursement-monitoring.view', 'fund-return-monitoring.view',
        'advances.view', 'advances.create', 'advances.update', 'advances.submit', 'advances.review', 'advances.request-revision', 'advances.validate', 'advances.approve', 'advances.reject', 'advances.disburse', 'advances.monitor', 'advances.download-attachment',
        'advance-settlements.view', 'advance-settlements.create', 'advance-settlements.update', 'advance-settlements.submit', 'advance-settlements.review', 'advance-settlements.request-revision', 'advance-settlements.verify', 'advance-settlements.approve', 'advance-settlements.reject', 'advance-settlements.download-attachment', 'advance-settlements.monitor',
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
                    'submissions.export', 'submissions.revise', 'submissions.resubmit',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
                    'fund-distributions.view', 'fund-distributions.download-proof',
                    'fund-receipts.view', 'fund-receipts.confirm',
                    'accountability-reports.view', 'accountability-reports.create', 'accountability-reports.update', 'accountability-reports.submit', 'accountability-reports.download-attachment',
                    'notifications.view', 'notifications.mark-read',
                    'reimbursements.view', 'reimbursements.create', 'reimbursements.update', 'reimbursements.submit', 'reimbursements.download-attachment',
                    'fund-returns.view', 'fund-returns.create', 'fund-returns.update', 'fund-returns.submit', 'fund-returns.download-attachment',
                ],
                'finance_staff' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'pics.view', 'pics.create', 'pics.update', 'pics.delete', 'pics.assign-cooperatives', 'submissions.export',
                    'finance-submissions.view', 'finance-submissions.review', 'finance-submissions.update',
                    'finance-submissions.request-revision', 'finance-submissions.validate', 'finance-submissions.forward-approval',
                    'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete', 'submissions.submit',
                    'finance-submissions.view-approval-revision', 'finance-submissions.update-approval-revision', 'finance-submissions.resubmit-approval',
                    'finance-monitoring.view',
                    'submission-categories.view', 'submission-categories.create', 'submission-categories.update', 'submission-categories.delete',
                    'submission-types.view', 'submission-types.create', 'submission-types.update', 'submission-types.delete',
                    'koperasi.assign-pic',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
                    'company-bank-accounts.view', 'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.create', 'fund-distributions.download-proof',
                    'disbursements.view', 'disbursements.download-proof',
                    'fund-receipts.view',
                    'accountability-reports.view', 'accountability-reports.review', 'accountability-reports.request-revision', 'accountability-reports.verify', 'accountability-reports.download-attachment',
                    'fund-monitoring.view',
                    'notifications.view', 'notifications.mark-read',
                    'reimbursements.view', 'reimbursements.create', 'reimbursements.update', 'reimbursements.submit', 'reimbursements.review', 'reimbursements.request-revision', 'reimbursements.validate', 'reimbursements.reject', 'reimbursements.download-attachment',
                    'fund-returns.view', 'fund-returns.review', 'fund-returns.request-revision', 'fund-returns.verify', 'fund-returns.reject', 'fund-returns.download-attachment',
                    'advances.view', 'advances.create', 'advances.update', 'advances.submit', 'advances.review', 'advances.request-revision', 'advances.validate', 'advances.download-attachment',
                    'advance-settlements.view', 'advance-settlements.create', 'advance-settlements.update', 'advance-settlements.submit', 'advance-settlements.review', 'advance-settlements.request-revision', 'advance-settlements.verify', 'advance-settlements.download-attachment',
                ],
                'finance_approver' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'pics.view', 'pics.create', 'pics.update', 'pics.delete', 'pics.assign-cooperatives', 'submissions.export',
                    'approval-submissions.view', 'approval-submissions.review', 'approval-submissions.approve',
                    'approval-submissions.reject', 'approval-submissions.request-revision',
                    'approval-submissions.view-director-revision', 'approval-submissions.update-director-revision', 'approval-submissions.resubmit-director',
                    'approval-monitoring.view',
                    'company-bank-accounts.view', 'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.monitor', 'fund-distributions.download-proof',
                    'disbursements.view', 'disbursements.download-proof',
                    'fund-receipts.view',
                    'accountability-reports.view', 'accountability-reports.approve', 'accountability-reports.download-attachment',
                    'fund-monitoring.view',
                    'koperasi.assign-pic',
                    'notifications.view', 'notifications.mark-read',
                    'reimbursements.view', 'reimbursements.approve', 'reimbursements.reject', 'reimbursements.request-revision', 'reimbursements.download-attachment',
                    'fund-returns.view', 'fund-returns.approve', 'fund-returns.reject', 'fund-returns.download-attachment', 'reimbursement-monitoring.view', 'fund-return-monitoring.view',
                    'advances.view', 'advances.approve', 'advances.reject', 'advances.monitor', 'advances.download-attachment',
                    'advance-settlements.view', 'advance-settlements.approve', 'advance-settlements.reject', 'advance-settlements.request-revision', 'advance-settlements.download-attachment', 'advance-settlements.monitor',
                ],
                'finance_director' => [
                    'dashboard.view', 'cooperatives.view', 'profile.view', 'profile.update',
                    'submissions.export',
                    'director-submissions.view', 'director-submissions.review', 'director-submissions.approve',
                    'director-submissions.disburse', 'director-submissions.reject', 'director-submissions.request-revision',
                    'director-submissions.view-all',
                    'director-monitoring.view', 'director-disbursements.view',
                    'disbursements.view', 'disbursements.create', 'disbursements.download-proof',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.update', 'bank-accounts.delete',
                    'company-bank-accounts.view', 'company-bank-accounts.create', 'company-bank-accounts.update', 'company-bank-accounts.delete', 'company-bank-accounts.set-primary',
                    'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.monitor', 'fund-distributions.download-proof',
                    'fund-receipts.view',
                    'accountability-reports.view', 'accountability-reports.download-attachment',
                    'fund-monitoring.view',
                    'notifications.view', 'notifications.mark-read',
                    'reimbursements.view', 'reimbursements.download-attachment', 'reimbursement-monitoring.view', 'fund-return-monitoring.view',
                    'advances.view', 'advances.disburse', 'advances.monitor', 'advances.download-attachment',
                    'advance-settlements.view', 'advance-settlements.download-attachment', 'advance-settlements.monitor',
                ],
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
