<?php

namespace App\Filament\Resources\Core\Employees;

use App\Filament\Resources\Core\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Core\Employees\Pages\EditEmployee;
use App\Filament\Resources\Core\Employees\Pages\ListEmployees;
use App\Filament\Resources\Core\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Core\Employees\Tables\EmployeesTable;
use App\Models\Core\Employee;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

       /**
     * Filtrage strict (pas juste un tri comme Customer/Transaction) :
     * un Director ne doit meme pas voir dans la liste les employes
     * d'une autre succursale. Complementaire de EmployeePolicy::view(),
     * qui bloque l'acces direct par URL — ici on bloque l'apparition
     * dans le tableau lui-meme.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user instanceof User && ! $user->isHeadOffice()) {
            $query->where('branch_id', $user->currentBranchId());
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
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
