<?php

namespace App\Filament\Resources\Core\P2pTransferFeeTiers;

use App\Filament\Resources\Core\P2pTransferFeeTiers\Pages\CreateP2pTransferFeeTier;
use App\Filament\Resources\Core\P2pTransferFeeTiers\Pages\EditP2pTransferFeeTier;
use App\Filament\Resources\Core\P2pTransferFeeTiers\Pages\ListP2pTransferFeeTiers;
use App\Filament\Resources\Core\P2pTransferFeeTiers\Schemas\P2pTransferFeeTierForm;
use App\Filament\Resources\Core\P2pTransferFeeTiers\Tables\P2pTransferFeeTiersTable;
use App\Models\Core\P2pTransferFeeTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class P2pTransferFeeTierResource extends Resource
{
    protected static ?string $model = P2pTransferFeeTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return P2pTransferFeeTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return P2pTransferFeeTiersTable::configure($table);
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
            'index' => ListP2pTransferFeeTiers::route('/'),
            // 'create' => CreateP2pTransferFeeTier::route('/create'),
            // 'edit' => EditP2pTransferFeeTier::route('/{record}/edit'),
        ];
    }
}
