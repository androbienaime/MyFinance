<?php

namespace App\Filament\Resources\Core\TrustedDevices;

use App\Filament\Resources\Core\TrustedDevices\Pages\ListTrustedDevices;
use App\Filament\Resources\Core\TrustedDevices\Tables\TrustedDevicesTable;
use App\Models\Core\TrustedDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class TrustedDeviceResource extends Resource
{
    protected static ?string $model = TrustedDevice::class;


    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|UnitEnum|null $navigationGroup = 'Sécurité';

    protected static ?string $navigationLabel = 'Appareils connus';

    protected static ?string $modelLabel = 'Appareil';

    protected static ?string $pluralModelLabel = 'Appareils connus';

    public static function canCreate(): bool
    {
        return false; // lecture seule + actions, pas de création manuelle
    }

    public static function table(Table $table): Table
    {
        return TrustedDevicesTable::configure($table);
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
            'index' => ListTrustedDevices::route('/'),
        ];
    }
}
