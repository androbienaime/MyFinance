<?php

namespace App\Filament\Resources\Core\Employees\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identite')
                ->columns(2)
                ->schema([
                    TextInput::make('firstname')->label('Prenom')->required(),
                    TextInput::make('lastname')->label('Nom')->required(),
                Grid::make(3)
                    ->schema([
                            Repeater::make('identityDocuments')
                                ->relationship('identityDocuments')
                                ->schema([
                                    Grid::make()
                                        ->columns(1)
                                        ->schema([
                                            Grid::make()
                                                ->columns(5)
                                                ->schema([
                                                    Select::make('document_type')
                                                        ->label(__('Document type'))
                                                        ->options([
                                                            'NIF' => 'NIF',
                                                            'NINU' => 'NINU',
                                                            'PASSPORT' => 'PASSPORT',
                                                            'DRIVING_LICENSE' => 'PERMIS DE CONDUIRE',
                                                        ])
                                                        ->default(fn () => 'NINU')
                                                        ->preload()
                                                        ->searchable()
                                                        ->live()
                                                        ->columnSpan(2)
                                                        ->afterStateUpdated(fn (callable $set) => $set('state_id', null)),
                                                
                                                    TextInput::make('document_number')
                                                        ->label(__('Document number'))
                                                        ->required()
                                                        ->columnSpan(3)
                                                        ->live(onBlur:true)
                                                        ->placeholder(fn (Get $get) => match ($get('document_type')) {
                                                            'NIF' => '008-739-938-5',
                                                            'NINU' => '0087399385',
                                                            'PASSPORT' => 'PA123456',
                                                            default => null,
                                                        })
                                                        ->mask(fn (Get $get) => match ($get('document_type')) {
                                                            'NIF' => RawJs::make("'999-999-999-9'"),
                                                            'NINU' => RawJs::make("'9999999999'"),
                                                            default => null,
                                                        })
                                                        ->rules(fn (Get $get) => match ($get('document_type')) {
                                                            'NIF' => ['regex:/^\d{3}-\d{3}-\d{3}-\d{1}$/'],
                                                            'NINU' => ['digits:10'],
                                                            'PASSPORT' => ['alpha_num', 'min:5', 'max:12'],
                                                            default => [],
                                                        }),
                                                    // Toggle::make('is_primary')
                                                    //     ->label(__('Is primary'))
                                                    //     ->default(fn () => true)
                                                    //     ->required()
                                                    //     ->columnSpan(1),
                                            ])->columnSpanFull(),
                                        ]),
                                ])
                                ->columnSpanFull()
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['document_type'] . ':'.$state['document_number'] ?? null),
                    ]),

                    Select::make('branch_id')
                        ->label('Succursale')
                        ->relationship('branch', 'name')
                        ->default(fn () => Auth::user()->currentBranchId())
                        ->disabled(fn () => ! Auth::user()->isHeadOffice())
                        ->visible(fn (string $operation) => $operation !== 'edit')
                        ->dehydrated()
                        ->required(),
                ]),

            Section::make('Compte de connexion')
                ->description('Cree automatiquement le compte utilisateur associe.')
                ->columns(2)
                ->schema([
                    TextInput::make('user_email')
                        ->label('Email de connexion')
                        ->email()
                        ->required()
                        // Unique sur la vraie table users, pas sur employees
                        ->unique(table: 'users', column: 'email', ignorable: fn ($record) => $record?->user)
                        // En edition, on affiche l'email existant mais on
                        // ne permet pas de le changer ici (ça reste une
                        // action distincte, plus sensible).
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated()
                        ->afterStateHydrated(function (TextInput $component, $record) {
                            if ($record?->user) {
                                $component->state($record->user->email);
                            }
                        }),

                    TextInput::make('user_password')
                        ->label('Mot de passe temporaire')
                        ->password()
                        ->revealable()
                        ->rule(Password::default())
                        // Uniquement demande a la creation : on ne modifie
                        // jamais un mot de passe existant depuis ce formulaire.
                        ->visible(fn (string $operation) => $operation === 'create')
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (string $operation) => $operation === 'create'),

                        // Visible uniquement si l'utilisateur courant a le droit
                        // de gerer les roles. Meme dans ce cas, la liste exclut
                        // les roles "siege" (system.full-access) sauf si c'est
                        // lui-meme un utilisateur siege - empeche un Director
                        // d'auto-escalader via un employe qu'il cree.
                    Select::make('role_id')
                        ->label('Role')
                        ->options(fn () => static::assignableRoles())
                        ->searchable()
                        ->visible(fn () => Auth::user()->can('roles.manage'))
                        ->helperText('Optionnel : peut aussi etre attribue plus tard depuis la fiche employe.')
                        // Le champ n'est pas lie a une vraie relation (justement pour eviter
                        // que Filament sync() directement sans passer par AssignRoleToUserAction),
                        // donc on doit pre-remplir sa valeur nous-memes en edition.
                        ->afterStateHydrated(function (Select $component, $record) {
                            if ($record?->user) {
                                $component->state($record->user->roles->first()?->id);
                            }
                        })
                        // Si le role actuel de l'employe est un role siege et que l'acteur
                        // courant n'est pas lui-meme siege, on verrouille le champ plutot
                        // que de risquer un changement qu'il n'a pas le droit de faire.
                        ->disabled(function ($record) {
                            if (! $record?->user) {
                                return false;
                            }

                            $currentRole = $record->user->roles->first();

                            return $currentRole?->isHeadOfficeRole() && ! Auth::user()->isHeadOffice();
                        }),
                    ]),
                ]);
    }

     /**
     * Liste des roles qu'un utilisateur donne a le droit de distribuer.
     * Reutilisee cote serveur dans CreateEmployee/EditEmployee pour
     * revalider, au cas ou le champ aurait ete manipule directement.
     */
    public static function assignableRoles()
    {
        return \App\Models\Core\Role::query()
        ->assignableBy(Auth::user())
        ->pluck('name', 'id');
    }
}
