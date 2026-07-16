<?php

namespace App\Filament\Resources\Core\TrustedDevices\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TrustedDevicesTable
{
     public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('device_label')
                    ->label('Appareil')
                    ->badge(),

                TextColumn::make('ip_address')
                    ->label('Dernière IP')
                    ->copyable(),

                TextColumn::make('login_count')
                    ->label('Connexions')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_trusted')
                    ->label('Fiable')
                    ->boolean(),

                TextColumn::make('first_seen_at')
                    ->label('Première connexion')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('last_seen_at')
                    ->label('Dernière activité')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->filters([
                TernaryFilter::make('is_trusted')
                    ->label('Statut de confiance')
                    ->trueLabel('Fiable')
                    ->falseLabel('Non fiable'),

                SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('trust')
                    ->label('Marquer comme fiable')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_trusted)
                    ->action(fn ($record) => $record->update(['is_trusted' => true])),

                Action::make('revoke')
                    ->label('Révoquer cet appareil')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('L\'utilisateur devra reconfirmer cet appareil à sa prochaine connexion depuis celui-ci.')
                    ->action(function ($record) {
                        // Déconnecte aussi la session active si elle correspond à cet appareil/IP
                        DB::table('sessions')
                            ->where('user_id', $record->user_id)
                            ->where('ip_address', $record->ip_address)
                            ->delete();

                        $record->delete();
                    }),

                Action::make('forceLogoutUser')
                    ->label('Déconnecter l\'utilisateur')
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        DB::table('sessions')->where('user_id', $record->user_id)->delete();

                        $record->user->notify(
                            new \App\Notifications\RemoteSessionTerminated(auth()->user()->name)
                        );
                    })
                    ->visible(fn () => auth()->user()->can('force_logout_users')),
            ])
            ->emptyStateHeading('Aucun appareil enregistré')
            ->emptyStateDescription('Les appareils apparaîtront ici après leur première connexion.');
    }
}
