<?php

namespace App\Providers;

use App\Models\Core\AccountClosure;
use App\Models\Core\Employee;
use App\Models\Core\LoginAttempt;
use App\Models\Core\P2pTransferFeeTier;
use App\Models\Core\P2pTransferLimit;
use App\Models\Core\P2pTransferRequest;
use App\Models\Core\PermissionLevelRequirement;
use App\Models\Core\Person;
use App\Models\Core\RoleAssignmentLog;
use App\Models\Core\Transaction;
use App\Models\Core\TrustedDevice;
use App\Observers\EmployeeObserver;
use App\Observers\TransactionObserver;
use App\Policies\AccountClosure as PoliciesAccountClosure;
use App\Policies\LoginAttemptPolicy;
use App\Policies\P2pTransferFeeTierPolicy;
use App\Policies\P2pTransferLimitPolicy;
use App\Policies\P2pTransferRequestPolicy;
use App\Policies\PermissionLevelRequirementPolicy;
use App\Policies\PersonPolicy;
use App\Policies\RoleAssignmentLogPolicy;
use App\Policies\RolePolicy;
use App\Policies\TrustedDevicePolicy;
use App\Services\SettingsOptionsResolver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        FilamentAsset::register([
            Js::make('case-grid', resource_path('js/filament/case-grid.js')),
        ]);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['en', 'fr', 'ht'])
            ->outsidePanelRoutes([
                'auth.login',
                'auth.register',
                'auth.password-reset.request',
                'auth.password-reset.reset',
            ]);
        });

        SettingsOptionsResolver::register('roles', function () {
            return \Spatie\Permission\Models\Role::pluck('name', 'id')->toArray();
        });

        SettingsOptionsResolver::register('branches', function () {
            return \App\Models\Core\Branch::pluck('name', 'id')->toArray();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(\App\Models\Core\Account::class, \App\Policies\AccountPolicy::class);
        Gate::policy(\App\Models\Core\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Core\Employee::class, \App\Policies\EmployeePolicy::class);
        Gate::policy(\App\Models\Core\Transaction::class, \App\Policies\TransactionPolicy::class);
        Gate::policy(\App\Models\Core\Branch::class, \App\Policies\BranchPolicy::class);
        Gate::policy(\App\Models\Core\ApprovalThreshold::class, \App\Policies\ApprovalThresholdPolicy::class);
        Gate::policy(\App\Models\Core\TypeOfAccount::class, \App\Policies\TypeOfAccountResourcePolicy::class);
        Gate::policy(AccountClosure::class, PoliciesAccountClosure::class);
        Gate::policy(Person::class, PersonPolicy::class);
        Gate::policy(LoginAttempt::class, LoginAttemptPolicy::class);
        Gate::policy(TrustedDevice::class, TrustedDevicePolicy::class);
        Gate::policy(P2pTransferFeeTier::class, P2pTransferFeeTierPolicy::class);
        Gate::policy(P2pTransferLimit::class, P2pTransferLimitPolicy::class);
        Gate::policy(P2pTransferRequest::class, P2pTransferRequestPolicy::class);
        Gate::policy(PermissionLevelRequirement::class, PermissionLevelRequirementPolicy::class);
        Gate::policy(RoleAssignmentLog::class, RoleAssignmentLogPolicy::class);

        Employee::observe(EmployeeObserver::class);
        Transaction::observe(TransactionObserver::class);




    }
}
