<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $pendingPermissionIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pull out every permissions_group_* field, collect the IDs,
        // and strip them so they never hit Role::create().
        $this->pendingPermissionIds = collect($data)
            ->filter(fn ($value, $key) => Str::startsWith($key, 'permissions_group_'))
            ->flatten()
            ->unique()
            ->values()
            ->all();


        return collect($data)
            ->reject(fn ($value, $key) => Str::startsWith($key, 'permissions_group_'))
            ->all();
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->pendingPermissionIds);
    }
}
