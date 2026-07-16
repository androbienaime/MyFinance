<?php

// app/Filament/Pages/Auth/ForcePasswordChange.php
namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChange extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.auth.force-password-change';

    public ?array $data = [];

    public function mount(): void
    {
        // Sécurité : si l'utilisateur n'a pas besoin de changer son mdp, on le renvoie ailleurs
        if (!Auth::user()?->must_change_password) {
            redirect()->intended(\Filament\Facades\Filament::getUrl());
        }

        $this->form->fill();
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('Mot de passe actuel')
                                    ->password()
                                    ->revealable()
                                    ->required(),

                                TextInput::make('new_password')
                                    ->label('Nouveau mot de passe')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->rule(Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised())
                                    ->different('current_password')
                                    ->helperText('Minimum 8 caractères, majuscules, minuscules, chiffres et symboles.'),

                                TextInput::make('new_password_confirmation')
                                    ->label('Confirmer le nouveau mot de passe')
                                    ->password()
                                    ->revealable()
                                    ->same('new_password')
                                    ->required(),
                            ])
                            ->columnSpan(1)
                            ->columnStart(['sm' => 2]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Mot de passe actuel incorrect')
                ->danger()
                ->send();
            return;
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        // Invalide les autres sessions par sécurité (bonne pratique après changement de mdp)
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        Notification::make()
            ->title('Mot de passe modifié avec succès')
            ->success()
            ->send();

        redirect()->intended(\Filament\Facades\Filament::getUrl());
    }
}