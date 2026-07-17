<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Core\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions'),

                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users'),

                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y'),
            ])->defaultSort('name')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->disabled(fn (Role $record) => !self::canUpdate($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('updated_at', 'desc');
    }


    public static function canUpdate(Role $role): bool
    {
        return $role->level !== 100 && $role->name !== 'super_admin';
    }
}
