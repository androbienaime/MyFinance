<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

     protected array $pendingPermissionIds = [];

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
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

    // protected function afterSave(): void
    // {
    //     $this->record->syncPermissions($this->pendingPermissionIds);
    // }


    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update([
            'name' => $data['name'],
            'level' => $data['level'], // vérifié automatiquement par Role::booted()
        ]);

        $record->syncPermissionsSecurely($this->extractRequestedPermissionIds($data));

        return $record;
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
