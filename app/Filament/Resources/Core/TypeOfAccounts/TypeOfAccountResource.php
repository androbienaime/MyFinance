<?php

namespace App\Filament\Resources\Core\TypeOfAccounts;

use App\Filament\Resources\Core\TypeOfAccounts\Pages\CreateTypeOfAccount;
use App\Filament\Resources\Core\TypeOfAccounts\Pages\EditTypeOfAccount;
use App\Filament\Resources\Core\TypeOfAccounts\Pages\ListTypeOfAccounts;
use App\Filament\Resources\Core\TypeOfAccounts\Schemas\TypeOfAccountForm;
use App\Filament\Resources\Core\TypeOfAccounts\Tables\TypeOfAccountsTable;
use App\Models\Core\TypeOfAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TypeOfAccountResource extends Resource
{
    protected static ?string $model = TypeOfAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return TypeOfAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TypeOfAccountsTable::configure($table);
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
            'index' => ListTypeOfAccounts::route('/'),
            // 'create' => CreateTypeOfAccount::route('/create'),
            // 'edit' => EditTypeOfAccount::route('/{record}/edit'),
        ];
    }
}
