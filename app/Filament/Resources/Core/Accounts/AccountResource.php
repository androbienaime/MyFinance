<?php

namespace App\Filament\Resources\Core\Accounts;

use App\Filament\Resources\Core\Accounts\Pages\CreateAccount;
use App\Filament\Resources\Core\Accounts\Pages\EditAccount;
use App\Filament\Resources\Core\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Core\Accounts\Schemas\AccountForm;
use App\Filament\Resources\Core\Accounts\Tables\AccountsTable;
use App\Models\Core\Account;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|UnitEnum|null $navigationGroup = 'Manage Accounts';


    public static function getNavigationLabel(): string
    {
        return __('myfinance.account');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.manage_accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
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
            'index' => ListAccounts::route('/'),
            // 'create' => CreateAccount::route('/create'),
            // 'edit' => EditAccount::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
