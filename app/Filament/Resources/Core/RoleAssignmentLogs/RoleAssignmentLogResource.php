<?php

namespace App\Filament\Resources\Core\RoleAssignmentLogs;

use App\Filament\Resources\Core\RoleAssignmentLogs\Pages\CreateRoleAssignmentLog;
use App\Filament\Resources\Core\RoleAssignmentLogs\Pages\EditRoleAssignmentLog;
use App\Filament\Resources\Core\RoleAssignmentLogs\Pages\ListRoleAssignmentLogs;
use App\Filament\Resources\Core\RoleAssignmentLogs\Schemas\RoleAssignmentLogForm;
use App\Filament\Resources\Core\RoleAssignmentLogs\Tables\RoleAssignmentLogsTable;
use App\Models\Core\RoleAssignmentLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RoleAssignmentLogResource extends Resource
{
    protected static ?string $model = RoleAssignmentLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Sécurité';

    public static function form(Schema $schema): Schema
    {
        return RoleAssignmentLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoleAssignmentLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoleAssignmentLogs::route('/'),
            'create' => CreateRoleAssignmentLog::route('/create'),
            'edit' => EditRoleAssignmentLog::route('/{record}/edit'),
        ];
    }
}
