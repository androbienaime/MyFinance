<?php

namespace App\Filament\Resources\Core\P2pTransferRequests;

use App\Filament\Resources\Core\P2pTransferRequests\Pages\CreateP2pTransferRequest;
use App\Filament\Resources\Core\P2pTransferRequests\Pages\EditP2pTransferRequest;
use App\Filament\Resources\Core\P2pTransferRequests\Pages\ListP2pTransferRequests;
use App\Filament\Resources\Core\P2pTransferRequests\Schemas\P2pTransferRequestForm;
use App\Filament\Resources\Core\P2pTransferRequests\Tables\P2pTransferRequestsTable;
use App\Models\Core\P2pTransferRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class P2pTransferRequestResource extends Resource
{
    protected static ?string $model = P2pTransferRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Sécurité';

    public static function form(Schema $schema): Schema
    {
        return P2pTransferRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return P2pTransferRequestsTable::configure($table);
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
            'index' => ListP2pTransferRequests::route('/'),
            'create' => CreateP2pTransferRequest::route('/create'),
            'edit' => EditP2pTransferRequest::route('/{record}/edit'),
        ];
    }
}
