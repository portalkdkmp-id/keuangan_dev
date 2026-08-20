<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRolePermissionFallbacks();
        $this->configureDefaults();
    }

    /**
     * Keep core role permissions available even when Spatie's permission cache is stale
     * after a database reset or role/user changes from the UI.
     */
    protected function configureRolePermissionFallbacks(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if (! str_contains($ability, '.')) {
                return null;
            }

            if ($user->hasRole('super_admin')) {
                return true;
            }

            $rolePermissions = [
                'finance_approver' => [
                    'pics.view', 'pics.assign-cooperatives',
                    'submissions.export',
                    'approval-submissions.view',
                    'approval-submissions.review',
                    'approval-submissions.approve',
                    'approval-submissions.reject',
                    'approval-submissions.request-revision',
                    'approval-submissions.view-director-revision',
                    'approval-submissions.update-director-revision',
                    'approval-submissions.resubmit-director',
                    'approval-monitoring.view',
                    'company-bank-accounts.view', 'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.monitor', 'fund-distributions.download-proof',
                    'disbursements.view', 'disbursements.download-proof',
                    'fund-receipts.view', 'accountability-reports.view', 'accountability-reports.approve',
                    'accountability-reports.download-attachment', 'fund-monitoring.view',
                ],
                'finance_staff' => [
                    'pics.view', 'pics.assign-cooperatives',
                    'submissions.export',
                    'finance-submissions.view',
                    'finance-submissions.review',
                    'finance-submissions.update',
                    'finance-submissions.request-revision',
                    'finance-submissions.validate',
                    'finance-submissions.forward-approval',
                    'finance-submissions.view-approval-revision',
                    'finance-submissions.update-approval-revision',
                    'finance-submissions.resubmit-approval',
                    'finance-monitoring.view',
                    'company-bank-accounts.view', 'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.create', 'fund-distributions.download-proof',
                    'disbursements.view', 'disbursements.download-proof',
                    'fund-receipts.view', 'accountability-reports.view', 'accountability-reports.review',
                    'accountability-reports.request-revision', 'accountability-reports.verify',
                    'accountability-reports.download-attachment', 'fund-monitoring.view',
                ],
                'finance_director' => [
                    'submissions.export',
                    'director-submissions.view',
                    'director-submissions.review',
                    'director-submissions.approve',
                    'director-submissions.disburse',
                    'director-submissions.reject',
                    'director-submissions.request-revision',
                    'director-submissions.view-all',
                    'director-monitoring.view',
                    'director-disbursements.view',
                    'disbursements.view',
                    'disbursements.create',
                    'disbursements.download-proof',
                    'bank-accounts.view',
                    'bank-accounts.create',
                    'bank-accounts.update',
                    'bank-accounts.delete',
                    'company-bank-accounts.view', 'company-bank-accounts.create', 'company-bank-accounts.update',
                    'company-bank-accounts.delete', 'company-bank-accounts.set-primary', 'cooperative-bank-accounts.view',
                    'fund-distributions.view', 'fund-distributions.monitor', 'fund-distributions.download-proof',
                    'fund-receipts.view', 'accountability-reports.view', 'accountability-reports.download-attachment',
                    'fund-monitoring.view',
                ],
                'pic_kdkmp' => [
                    'submissions.export',
                    'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete',
                    'submissions.submit', 'submissions.revise', 'submissions.resubmit',
                    'reimbursements.view', 'reimbursements.create', 'reimbursements.update',
                    'reimbursements.submit', 'reimbursements.download-attachment',
                    'fund-distributions.view', 'fund-distributions.download-proof',
                    'fund-receipts.view', 'fund-receipts.confirm',
                    'accountability-reports.view', 'accountability-reports.create', 'accountability-reports.update',
                    'accountability-reports.submit', 'accountability-reports.download-attachment',
                ],
            ];

            foreach ($rolePermissions as $role => $permissions) {
                if ($user->hasRole($role) && in_array($ability, $permissions, true)) {
                    return true;
                }
            }

            return null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
