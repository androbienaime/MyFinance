<?php

namespace App\Filament\Resources\Core\MerchantApiKeys;

use App\Filament\Resources\Core\MerchantApiKeys\Pages\CreateMerchantApiKey;
use App\Filament\Resources\Core\MerchantApiKeys\Pages\EditMerchantApiKey;
use App\Filament\Resources\Core\MerchantApiKeys\Pages\ListMerchantApiKeys;
use App\Filament\Resources\Core\MerchantApiKeys\Schemas\MerchantApiKeyForm;
use App\Filament\Resources\Core\MerchantApiKeys\Tables\MerchantApiKeysTable;
use App\Models\Core\MerchantApiKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MerchantApiKeyResource extends Resource
{
    protected static ?string $model = MerchantApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Key;

    protected static string|UnitEnum|null $navigationGroup = 'settings';

    public static function getNavigationGroup(): string
    {
        return __('myfinance.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return MerchantApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantApiKeysTable::configure($table);
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
            'index' => ListMerchantApiKeys::route('/'),
            'create' => CreateMerchantApiKey::route('/create'),
            'edit' => EditMerchantApiKey::route('/{record}/edit'),
        ];
    }
}
