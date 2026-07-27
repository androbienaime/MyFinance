<?php

namespace App\Filament\Resources\Core\SystemUpdates;

use App\Filament\Resources\Core\SystemUpdates\Pages\CreateSystemUpdate;
use App\Filament\Resources\Core\SystemUpdates\Pages\EditSystemUpdate;
use App\Filament\Resources\Core\SystemUpdates\Pages\ListSystemUpdates;
use App\Filament\Resources\Core\SystemUpdates\Schemas\SystemUpdateForm;
use App\Filament\Resources\Core\SystemUpdates\Tables\SystemUpdatesTable;
use App\Models\Core\SystemUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SystemUpdateResource extends Resource
{
    protected static ?string $model = SystemUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InformationCircle;
    protected static string|UnitEnum|null $navigationGroup = 'settings';

    public static function getNavigationGroup(): string
    {
        return __('myfinance.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return SystemUpdateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemUpdatesTable::configure($table);
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
            'index' => ListSystemUpdates::route('/'),
            'create' => CreateSystemUpdate::route('/create'),
            'edit' => EditSystemUpdate::route('/{record}/edit'),
        ];
    }
}
