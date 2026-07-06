<?php

namespace App\Filament\Resources\Core\Branches;

use App\Filament\Resources\Core\Branches\Pages\CreateBranch;
use App\Filament\Resources\Core\Branches\Pages\EditBranch;
use App\Filament\Resources\Core\Branches\Pages\ListBranches;
use App\Filament\Resources\Core\Branches\Schemas\BranchForm;
use App\Filament\Resources\Core\Branches\Tables\BranchesTable;
use App\Models\Core\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;
        protected static string|UnitEnum|null $navigationGroup = 'Administration';


    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
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
            'index' => ListBranches::route('/'),
            // 'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
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
