<?php

namespace App\Filament\Resources\Core\Employees\Tables;

use App\Filament\Resources\Core\Employees\Actions\TransferAction;
use App\Models\Core\Employee;
use App\Notifications\RemoteSessionTerminated;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('gender')
                    ->searchable(),
                TextColumn::make('identity_number')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('address.id')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('user.is_active')
                    ->label(__("myfinance.user_active"))
                    ->boolean(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                TransferAction::make(),
                Action::make('forceLogout')
                ->label('Déconnecter à distance')
                ->icon('heroicon-o-power')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Employee $record) {
                    DB::table('sessions')->where('user_id', $record->user_id)->delete();
                    $record->notify(new RemoteSessionTerminated($record?->person?->full_name));

                    \Filament\Notifications\Notification::make()
                        ->title('Utilisateur déconnecté avec succès')
                        ->success()
                        ->send()
                        ->sendToDatabase(Auth::user());
                })
                ->visible(fn () => auth()->user()->can('users.force_logout')),
                Action::make('deactivate')
                ->label('Désactiver')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn ($record) => auth()->user()->can('users.deactivate', $record))
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')
                        ->label('Motif de désactivation')
                        ->required()
                        ->minLength(5)
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    $record->user->deactivate($data['reason'], auth()->user());

                    \Filament\Notifications\Notification::make()
                        ->title('Utilisateur désactivé')
                        ->success()
                        ->send();
                }),

                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user->reactivate())
                    ->visible(fn ($record) => auth()->user()->can('users.reactivate', $record))
,
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])->defaultSort('updated_at', 'desc');
    }
}
