<?php

namespace App\Providers;

use App\Policies\RolePolicy;
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
        //
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
    }
}
