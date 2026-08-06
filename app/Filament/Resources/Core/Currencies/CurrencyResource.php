<?php

namespace App\Filament\Resources\Core\Currencies;

use App\Filament\Resources\Core\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Core\Currencies\Pages\EditCurrency;
use App\Filament\Resources\Core\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Core\Currencies\Schemas\CurrencyForm;
use App\Filament\Resources\Core\Currencies\Tables\CurrenciesTable;
use App\Models\Core\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'settings';

    public static function getNavigationGroup(): string
    {
        return __('myfinance.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
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
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
