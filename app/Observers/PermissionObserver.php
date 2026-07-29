<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;
use App\Models\Core\Role;

class PermissionObserver
{
    public function created(Permission $permission): void
    {
        $this->resyncSuperAdmin();
    }

    // public function updated(Permission $permission): void
    // {
    //     $this->resyncSuperAdmin();
    // }

    public function deleted(Permission $permission): void
    {
        $this->resyncSuperAdmin();
    }

    protected function resyncSuperAdmin(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();

        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}