<?php

namespace App\Filament\Resources\Core\MerchantProfiles\Tables;

use App\Actions\ApproveMerchantProfileAction;
use App\Enums\MerchantStatus;
use App\Models\Core\MerchantProfile;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MerchantProfilesTable
{
    public static function configure(Table $table): Table
    {
         return $table
            ->columns([
                TextColumn::make('business_name')->label('Nom commercial')->searchable()->sortable(),
                TextColumn::make('account.code')->label('Compte')->searchable(),
                TextColumn::make('category')->label('Categorie')->placeholder('—'),
                TextColumn::make('status')
                ->label('Statut')
                ->badge()
                ->formatStateUsing(fn (MerchantStatus $state) => match ($state) {
                    MerchantStatus::Pending => 'En attente',
                    MerchantStatus::Active => 'Actif',
                    MerchantStatus::Suspended => 'Suspendu',
                    MerchantStatus::Rejected => 'Rejete',
                })
                ->color(fn (MerchantStatus $state) => match ($state) {
                    MerchantStatus::Pending => 'warning',
                    MerchantStatus::Active => 'success',
                    MerchantStatus::Suspended => 'danger',
                    MerchantStatus::Rejected => 'gray',
                }),
                TextColumn::make('transaction_fee_percentage')->label('Frais')->suffix('%')->placeholder('Defaut global'),
                TextColumn::make('created_at')->label('Cree le')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Statut')->options([
                    'pending' => 'En attente', 'active' => 'Actif',
                    'suspended' => 'Suspendu', 'rejected' => 'Rejete',
                ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (MerchantProfile $r) => $r->status === MerchantStatus::Pending && Auth::user()->can('approve', $r))
                    ->requiresConfirmation()
                    ->action(function (MerchantProfile $record) {
                        app(ApproveMerchantProfileAction::class)->approve($record, Auth::user());
                        Notification::make()->title('Marchand approuve.')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Rejeter')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (MerchantProfile $r) => $r->status === MerchantStatus::Pending && Auth::user()->can('approve', $r))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('Motif du rejet')->required()])
                    ->action(function (MerchantProfile $record, array $data) {
                        app(ApproveMerchantProfileAction::class)->reject($record, Auth::user(), $data['reason']);
                        Notification::make()->title('Marchand rejete.')->success()->send();
                    }),

                Action::make('suspend')
                    ->label('Suspendre')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (MerchantProfile $r) => $r->status === MerchantStatus::Active && Auth::user()->can('suspend', $r))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('Motif de la suspension')->required()])
                    ->action(function (MerchantProfile $record, array $data) {
                        app(ApproveMerchantProfileAction::class)->suspend($record, Auth::user(), $data['reason']);
                        Notification::make()->title('Marchand suspendu.')->success()->send();
                    }),

                EditAction::make()->visible(fn (MerchantProfile $r) => Auth::user()->can('update', $r)),
            ]);
    }
}
