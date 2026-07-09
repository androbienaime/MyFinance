<?php

namespace App\Filament\Resources\Core\Customers\Schemas;

use App\Models\Core\City;
use App\Models\Core\Country;
use App\Models\Core\State;
use App\Models\Core\TypeOfAccount;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                ->schema([
                    Fieldset::make('Informations personnelles')
                    ->columns(1)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->visible(false),
                        TextInput::make('person.employee_id')
                                        ->label('Cree par')
                                        ->default(fn () => Auth()->user()->employee?->id)
                                        ->disabled()
                                        ->required()
                                        ->visible(false),
                        Section::make()
                            ->relationship('person')
                            ->schema([
                                Grid::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('first_name')
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->required(),
                                    Select::make('gender')
                                        ->options([
                                            'male' => 'Masculin',
                                            'female' => 'Feminin',
                                        ])
                                    ->default(fn () => 'male'),
                            Repeater::make('identityDocuments')
                                ->relationship('identityDocuments')
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
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
                                                        })
                                                        ->columnSpan(3),
                                                    // Toggle::make('is_primary')
                                                    //     ->label(__('Is primary'))
                                                    //     ->default(fn () => true)
                                                    //     ->required()
                                                    //     ->columnSpan(1),
                                            ])->columnSpanFull(),
                                        ]),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['document_type'] . ':'.$state['document_number'] ?? null),
                            ]),
                        
                            Repeater::make('addresses')
                                ->relationship('addresses')
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            Select::make('country_id')
                                                ->label('Country')
                                                ->options(fn () => Country::all()->pluck('name', 'id'))
                                                ->default(fn () => Country::where("name", "Haiti")->first()->id)
                                                ->preload()
                                                ->searchable()
                                                ->live()
                                                ->afterStateUpdated(fn (callable $set) => $set('state_id', null)),

                                            Select::make('state_id')
                                                ->label('State')
                                                ->options(fn (callable $get) => State::where('country_id', $get('country_id'))
                                                    ->pluck('name', 'id')
                                                    ->toArray())
                                                ->default(fn () => State::where("name", "Nord-Est")->first()->id)
                                                ->live()
                                                ->searchable()
                                                ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),

                                            Select::make('city_id')
                                                ->label('City')
                                                ->options(fn (callable $get) => City::where('state_id', $get('state_id'))
                                                    ->pluck('name', 'id')
                                                    ->toArray())
                                                ->default(fn () => City::where("name", "Trou-du-Nord")->first()->id)
                                                ->live()
                                                ->searchable(),

                                            TextInput::make('address1')->label('Address 1'),
                                            TextInput::make('phone')->label('Phone'),
                                            TextInput::make('email')->label('Email'),
                                        ]),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['address1'] ?? null),
                            ]),
                    ])->columnSpanFull(),
                    Section::make('Compte initial')
                    ->description('Un compte est obligatoirement cree avec le client.')
                    ->columns(2)
                    ->schema([
                        Select::make('type_of_account_id')
                            ->label('Type de compte')
                            ->options(TypeOfAccount::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->native(false),
                        Section::make('Personnes associees au compte')
                            ->description('Ajoute les personnes qui auront un role sur ce compte, en plus du titulaire principal.')
                            ->schema([
                                Repeater::make('additional_account_people')
                                    ->label('')
                                    // Pas de ->relationship() ici : rien n'existe encore en base.
                                    // C'est un simple tableau d'etat, traite manuellement dans
                                    // CreateCustomer::handleRecordCreation().
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('first_name')->label('Prenom')->required(),
                                            TextInput::make('last_name')->label('Nom')->required(),

                                            Select::make('role')
                                                ->label('Role')
                                                ->live()
                                                ->options([
                                                    'co_owner' => 'Cotitulaire',
                                                    'attorney' => 'Mandataire',
                                                    'beneficiary' => 'Beneficiaire',
                                                    'guardian' => 'Representant legal',
                                                ])
                                                ->required(),

                                            TextInput::make('share_percentage')
                                                ->label('Part (%)')
                                                ->numeric()
                                                ->suffix('%')
                                                ->visible(fn ($get) => $get('role') === 'beneficiary'),

                                    
                                            Select::make('gender')
                                                ->label('Genre')
                                                ->options(['male' => 'Masculin', 'female' => 'Feminin']),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['first_name'] ?? null),
                            ])
                            ->visible(fn (string $operation) => $operation === 'create'),

                        // TextInput::make('initial_balance')
                        //     ->label('Depot initial')
                        //     ->numeric()
                        //     ->default(0)
                        //     ->minValue(0)
                        //     ->required()
                        //     ->visible(false),
                    ])
                    // Uniquement a la creation - on ne veut pas permettre de
                    // recreer un compte depuis le formulaire d'edition du client.
                    ->visible(fn (string $operation) => $operation === 'create'),
                    // Select::make('employee_id')
                    //         ->label('Cree par')
                    //         ->relationship('employee', 'firstname')
                    //         ->default(fn () => Auth::user()->employee?->id)
                    //         ->disabled()
                    //         // ->dehydrated()
                    //         ->required()
                    //         // ->visible(false),
                ])->columns(1)
                ->columnSpan(3),
                Grid::make()
                ->schema([
                    //
                ])
            ])->columns(4);
    }
}
