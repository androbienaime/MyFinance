<?php

namespace App\Providers;

use App\Models\Core\AccountClosure;
use App\Models\Core\Employee;
use App\Models\Core\Person;
use App\Models\Core\Transaction;
use App\Observers\EmployeeObserver;
use App\Observers\TransactionObserver;
use App\Policies\AccountClosure as PoliciesAccountClosure;
use App\Policies\PersonPolicy;
use App\Policies\RolePolicy;
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
        
        Employee::observe(EmployeeObserver::class);
        Transaction::observe(TransactionObserver::class);


    }
}
