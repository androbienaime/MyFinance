<?php

namespace App\Filament\Resources\Core\MerchantProfiles;

use App\Filament\Resources\Core\MerchantProfiles\Pages\CreateMerchantProfiles;
use App\Filament\Resources\Core\MerchantProfiles\Pages\EditMerchantProfiles;
use App\Filament\Resources\Core\MerchantProfiles\Pages\ListMerchantProfiles;
use App\Filament\Resources\Core\MerchantProfiles\Tables\MerchantProfilesTable;
use App\Models\Core\MerchantProfile;
use App\Models\Core\MerchantProfiles;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MerchantProfilesResource extends Resource
{
    protected static ?string $model = MerchantProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Manage_Accounts';

    public static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('myfinance.manage_accounts');
    }
    public static function form(Schema $schema): Schema
    {
        return MerchantProfile::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantProfilesTable::configure($table);
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
            'index' => ListMerchantProfiles::route('/'),
            'create' => CreateMerchantProfiles::route('/create'),
            'edit' => EditMerchantProfiles::route('/{record}/edit'),
        ];
    }
}
