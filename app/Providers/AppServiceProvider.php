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
                    'approval-submissions.view',
                    'approval-submissions.review',
                    'approval-submissions.approve',
                    'approval-submissions.reject',
                    'approval-submissions.request-revision',
                    'approval-submissions.view-director-revision',
                    'approval-submissions.update-director-revision',
                    'approval-submissions.resubmit-director',
                    'approval-monitoring.view',
                ],
                'finance_staff' => [
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
                ],
                'finance_director' => [
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
