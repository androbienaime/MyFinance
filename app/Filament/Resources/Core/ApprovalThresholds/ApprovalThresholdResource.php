<?php

namespace App\Filament\Resources\Core\ApprovalThresholds;

use App\Filament\Resources\Core\ApprovalThresholds\Pages\CreateApprovalThreshold;
use App\Filament\Resources\Core\ApprovalThresholds\Pages\EditApprovalThreshold;
use App\Filament\Resources\Core\ApprovalThresholds\Pages\ListApprovalThresholds;
use App\Filament\Resources\Core\ApprovalThresholds\Schemas\ApprovalThresholdForm;
use App\Filament\Resources\Core\ApprovalThresholds\Tables\ApprovalThresholdsTable;
use App\Models\Core\ApprovalThreshold;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApprovalThresholdResource extends Resource
{
    protected static ?string $model = ApprovalThreshold::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ApprovalThresholdForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalThresholdsTable::configure($table);
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
            'index' => ListApprovalThresholds::route('/'),
            'create' => CreateApprovalThreshold::route('/create'),
            'edit' => EditApprovalThreshold::route('/{record}/edit'),
        ];
    }
}
