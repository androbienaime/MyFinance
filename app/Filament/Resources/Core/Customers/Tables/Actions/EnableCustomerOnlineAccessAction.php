<?php

// app/Filament/Resources/Core/Customers/Tables/Actions/EnableCustomerOnlineAccessAction.php
namespace App\Filament\Resources\Core\Customers\Tables\Actions;

use App\Models\Core\Customer;
use App\Notifications\CustomerActivationCode;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EnableCustomerOnlineAccessAction
{
    public static function make(): Action
    {
        return Action::make('enableOnlineAccess')
            ->label(fn (Customer $record) => $record->is_online ? 'Acces en ligne actif' : 'Activer l\'acces en ligne')
            ->icon('heroicon-o-signal')
            ->color(fn (Customer $record) => $record->is_online ? 'gray' : 'success')
            ->disabled(fn (Customer $record) => (bool) $record->is_online)
            ->visible(fn (Customer $record) => auth()->user()->can('customers.enable_online_access'))
            ->requiresConfirmation(fn (Customer $record) => filled($record->email) || filled($record->phone_number))
            ->modalHeading('Activer l\'acces en ligne')
            ->modalDescription('Un code d\'activation sera envoye au client par WhatsApp. Il pourra alors definir son mot de passe et se connecter a l\'application.')
            ->schema(function (Customer $record) {
                // Formulaire affiche uniquement si email ET telephone sont
                // tous les deux absents - on force a en renseigner au moins
                // un avant de pouvoir continuer, sans quoi aucun canal
                // n'existe pour recevoir le code d'activation.
                if (filled($record->email) || filled($record->phone_number)) {
                    return [];
                }

                return [
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->unique(table: 'customers', column: 'email')
                        ->helperText('Renseignez au moins un des deux champs (email ou telephone).'),

                    TextInput::make('phone_number')
                        ->label('Telephone')
                        ->tel(),
                ];
            })
            ->action(function (Customer $record, array $data) {
                // Si le formulaire etait vide (email/phone deja presents),
                // $data ne contient rien - on ne touche pas au customer.
                if (filled($data['email'] ?? null) || filled($data['phone_number'] ?? null)) {
                    $validator = Validator::make($data, [
                        'email' => ['nullable', 'email', 'unique:customers,email'],
                        'phone_number' => ['nullable', 'string'],
                    ]);

                    if (blank($data['email'] ?? null) && blank($data['phone_number'] ?? null)) {
                        Notification::make()
                            ->title('Email ou telephone requis')
                            ->body('Renseignez au moins un des deux champs pour continuer.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($validator->fails()) {
                        Notification::make()
                            ->title('Donnees invalides')
                            ->body($validator->errors()->first())
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->update(array_filter([
                        'email' => $data['email'] ?? null,
                        'phone_number' => $data['phone_number'] ?? null,
                    ]));
                }

                if (blank($record->email) && blank($record->phone_number)) {
                    Notification::make()
                        ->title('Impossible d\'activer')
                        ->body('Ce client n\'a ni email ni telephone enregistre.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $code = (string) random_int(100000, 999999);

                    $record->update([
                        'activation_code_hash' => Hash::make($code),
                        'activation_expires_at' => now()->addDays(3),
                        'is_online' => true,
                    ]);

                    $record->notify(new CustomerActivationCode($code));

                    Notification::make()
                        ->title('Acces en ligne active')
                        ->body("Un code d'activation a ete envoye au client.")
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Echec de l\'activation')
                        ->body('Une erreur est survenue - le client n\'a pas ete marque comme actif.')
                        ->danger()
                        ->send();
                }
            });
    }
}