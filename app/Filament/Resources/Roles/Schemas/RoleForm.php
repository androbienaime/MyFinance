<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function permissionGroups(): Collection
    {
        $labels = [
            'system' => 'Systeme',
            'transactions' => 'Transactions',
            'accounts' => 'Comptes',
            'customers' => 'Clients',
            'employees' => 'Employes',
            'branches' => 'Succursales',
            'approval-thresholds' => 'Seuils d\'approbation',
            'roles' => 'Roles & permissions',
            'reports' => 'Rapports',
            'users' => 'Utilisateurs',
        ];

        return Permission::all()
            ->groupBy(fn (Permission $permission) => Str::before($permission->name, '.'))
            ->map(fn ($permissions, $prefix) => [
                'label' => $labels[$prefix] ?? Str::headline($prefix),
                'permissions' => $permissions->pluck('name', 'id'),
            ])
            ->sortBy(fn (array $group) => $group['label']);
    }

    
    public static function configure(Schema $schema): Schema
    {
        $groups = static::permissionGroups();

        return $schema->components([
            // ==========================
            // Informations du rôle
            // ==========================
            Grid::make()
                ->columns(1)
                ->schema([
                    Section::make('Informations du rôle')
                        ->icon('heroicon-o-shield-check')
                        ->description('Définissez les informations principales du rôle.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nom du rôle')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100),

                            TextInput::make('guard_name')
                                ->label('Guard')
                                ->default('web')
                                ->disabled()
                                ->dehydrated(),
                        ]),
                ]),

            // ==========================
            // Permissions
            // ==========================
            Grid::make([
                    'default' => 1,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->gap(5)
                ->schema(
                    $groups->map(function (array $group, string $prefix) {

                        return Section::make($group['label'])
                            ->icon('heroicon-o-lock-closed')
                            ->description('Sélectionnez les permissions.')
                            ->collapsible()
                            ->compact()
                            ->schema([
                                CheckboxList::make("permissions_group_{$prefix}")
                                    ->label('')
                                    ->options($group['permissions'])
                                    ->columns(2)
                                    ->gridDirection('row')
                                    ->bulkToggleable()
                                    ->afterStateHydrated(function (CheckboxList $component, $record) use ($group) {
                                        if (! $record) {
                                            return;
                                        }
                                        $component->state(
                                            $record->permissions
                                                ->pluck('id')
                                                ->intersect($group['permissions']->keys())
                                                ->values()
                                                ->all()
                                        );
                                    }),
                            ]);

                    })->values()->all()
                ),
        ])->columns(1);
    }
}