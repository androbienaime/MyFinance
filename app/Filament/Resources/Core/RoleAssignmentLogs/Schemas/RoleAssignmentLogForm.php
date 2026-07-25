<?php

namespace App\Filament\Resources\Core\RoleAssignmentLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleAssignmentLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('role_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('assigned_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('assigned_at')
                    ->required(),
            ]);
    }
}
