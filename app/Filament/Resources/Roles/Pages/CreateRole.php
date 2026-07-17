<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $pendingPermissionIds = [];

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Pull out every permissions_group_* field, collect the IDs,
    //     // and strip them so they never hit Role::create().
    //     $this->pendingPermissionIds = collect($data)
    //         ->filter(fn ($value, $key) => Str::startsWith($key, 'permissions_group_'))
    //         ->flatten()
    //         ->unique()
    //         ->values()
    //         ->all();


    //     return collect($data)
    //         ->reject(fn ($value, $key) => Str::startsWith($key, 'permissions_group_'))
    //         ->all();
    // }

    // protected function afterCreate(): void
    // {
    //     $this->record->syncPermissions($this->pendingPermissionIds);
    // }


        // app/Filament/Resources/Roles/Pages/CreateRole.php
    protected function handleRecordCreation(array $data): Model
    {
        $role = static::getModel()::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'level' => $data['level'], // vérifié automatiquement par Role::booted()
        ]);

        $role->syncPermissionsSecurely($this->extractRequestedPermissionIds($data));

        return $role;
    }

    protected function extractRequestedPermissionIds(array $data): array
    {
        $ids = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_group_') && is_array($value)) {
                $ids = array_merge($ids, $value);
            }
        }
        return array_unique($ids);
    }
}
