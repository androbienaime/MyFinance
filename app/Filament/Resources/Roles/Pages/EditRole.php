<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

     protected array $pendingPermissionIds = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->pendingPermissionIds);
    }
}
