<?php

namespace App\Filament\Resources\Core\P2pTransferLimits;

use App\Filament\Resources\Core\P2pTransferLimits\Pages\CreateP2pTransferLimit;
use App\Filament\Resources\Core\P2pTransferLimits\Pages\EditP2pTransferLimit;
use App\Filament\Resources\Core\P2pTransferLimits\Pages\ListP2pTransferLimits;
use App\Filament\Resources\Core\P2pTransferLimits\Schemas\P2pTransferLimitForm;
use App\Filament\Resources\Core\P2pTransferLimits\Tables\P2pTransferLimitsTable;
use App\Models\Core\P2pTransferLimit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class P2pTransferLimitResource extends Resource
{
    protected static ?string $model = P2pTransferLimit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return P2pTransferLimitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return P2pTransferLimitsTable::configure($table);
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
            'index' => ListP2pTransferLimits::route('/'),
            // 'create' => CreateP2pTransferLimit::route('/create'),
            // 'edit' => EditP2pTransferLimit::route('/{record}/edit'),
        ];
    }
}
