<?php

namespace App\Filament\Resources\Core\Customers;

use App\Filament\Resources\Core\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Core\Customers\Pages\EditCustomer;
use App\Filament\Resources\Core\Customers\Pages\ListCustomers;
use App\Filament\Resources\Core\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Core\Customers\Tables\CustomersTable;
use App\Models\Core\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;
    protected static string|UnitEnum|null $navigationGroup = 'Manage Accounts';

    public static function getNavigationLabel(): string
    {
        return __('myfinance.customer');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.manage_accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
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
