<?php

namespace App\Filament\Resources\Core\Customers\Schemas;

use App\Enums\AccountHolderType;
use App\Models\Core\City;
use App\Models\Core\Country;
use App\Models\Core\Currency;
use App\Models\Core\State;
use App\Models\Core\TypeOfAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Contracts\Database\Query\Builder;
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
                                        ->label(__('myfinance.first_name'))
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->label(__('myfinance.last_name'))
                                        ->required(),
                                    Select::make('gender')
                                        ->label(__('myfinance.gender'))
                                        ->options([
                                            'male' => 'Masculin',
                                            'female' => 'Feminin',
                                        ])
                                    ->default(fn () => 'male'),
                            Repeater::make('identityDocuments')
                                ->label(__('myfinance.identity_documents'))
                                ->relationship('identityDocuments')
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            Grid::make()
                                                ->columns(5)
                                                ->schema([
                                                    Select::make('document_type')
                                                        ->label(__('myfinance.document_type'))
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
                                                        ->label(__('myfinance.document_number'))
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
                            
                            DatePicker::make("date_of_birth")
                                ->label(__("myfinance.date_of_birth"))
                                ->maxDate(now()->subYears(5)),
                            Select::make("marital_status")
                                ->label(__("myfinance.marital_status"))
                                ->options([
                                    'single' => 'Célibataire',
                                    'married' => 'Marié(e)',
                                    'divorced' => 'Divorcé(e)',
                                    'widowed' => 'Veuf(ve)',
                                ]),
                            ]),
                            
                            Repeater::make('addresses')
                                ->relationship('addresses')
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            Select::make('country_id')
                                                ->label(__('myfinance.country'))
                                                ->options(fn () => Country::all()->pluck('name', 'id'))
                                                ->default(fn () => Country::where("name", "Haiti")->first()->id)
                                                ->preload()
                                                ->searchable()
                                                ->live()
                                                ->afterStateUpdated(fn (callable $set) => $set('state_id', null)),

                                            Select::make('state_id')
                                                ->label(__('myfinance.state'))
                                                ->options(fn (callable $get) => State::where('country_id', $get('country_id'))
                                                    ->pluck('name', 'id')
                                                    ->toArray())
                                                ->default(fn () => State::where("name", "Nord-Est")->first()->id)
                                                ->live()
                                                ->searchable()
                                                ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),

                                            Select::make('city_id')
                                                ->label(__('myfinance.city'))
                                                ->options(fn (callable $get) => City::where('state_id', $get('state_id'))
                                                    ->pluck('name', 'id')
                                                    ->toArray())
                                                ->default(fn () => City::where("name", "Trou-du-Nord")->first()->id)
                                                ->live()
                                                ->searchable(),

                                            TextInput::make('address1')->label(__('myfinance.address1')),
                                            TextInput::make('phone')->label(__('myfinance.phone')),
                                            TextInput::make('email')->label(__('myfinance.email')),
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
                        // Select::make('type_of_account_id')
                        //     ->label('Type de compte')
                        //     ->options(TypeOfAccount::pluck('name', 'id'))
                        //     ->searchable()
                        //     ->required()
                        //     ->native(false),

            Select::make('type_of_account_id')
                ->label('Type de compte')
                ->relationship(
                        name: 'accounts.typeOfAccount',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $get('holder_type') === AccountHolderType::Merchant->value
                            ? $query->where('active_case_payments', false)
                            : $query,
                    )
                ->live()
                ->required(),

            Select::make('currency_id')
            ->label('Devise')
            ->relationship("accounts.currency", "name")
            ->default(fn ()=> Currency::where("iso_code", setting("financial.default_currency", default:'HTG'))->first()->id)
            ->getOptionLabelFromRecordUsing(
                fn ($record) => "{$record->name}"
            )
            ->required(),
            
            Select::make('holder_type')
                ->label('Type de titulaire')
                ->options([
                    AccountHolderType::Personal->value => 'Personnel',
                    AccountHolderType::Merchant->value => 'Marchand',
                ])
                ->native(false)
                ->default(AccountHolderType::Personal->value)
                ->live()
                ->required()
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated()
                // Si le type de compte deja selectionne devient incompatible avec
                // le nouveau holder_type (cas a cases + marchand), on le vide plutot
                // que de laisser une valeur invalide en attente de soumission.
                ->afterStateUpdated(function ($state, Get $get, callable $set) {
                    if ($state !== AccountHolderType::Merchant->value) {
                        return;
                    }

                    $currentTypeId = $get('type_of_account_id');

                    if (! $currentTypeId) {
                        return;
                    }

                    $type = \App\Models\Core\TypeOfAccount::find($currentTypeId);

                    if ($type && (bool) $type->active_case_payments) {
                        $set('type_of_account_id', null);
                    }
                }),

            Section::make('Informations commerciales')
                ->columns(2)
                ->visible(fn (Get $get) => $get('holder_type') === AccountHolderType::Merchant->value)
                ->schema([
                    TextInput::make('merchant_business_name')
                        ->label('Nom commercial')
                        ->required(fn (Get $get) => $get('holder_type') === AccountHolderType::Merchant->value)
                        ->columnSpanFull(),

                    TextInput::make('merchant_category')
                        ->label('Categorie'),

                    TextInput::make('merchant_business_registration_number')
                        ->label('NIF / Patente'),

                    Textarea::make('merchant_address')
                        ->label('Adresse')
                        ->columnSpanFull(),
                ])->columnSpanFull(),

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
                    Section::make()
                        ->schema([
                            Select::make('phonecode')
                                ->label(__("myfinance.phone_code"))
                                ->options(fn () => Country::query()
                                    ->pluck('phonecode', 'phonecode'))
                                ->default(fn () => Country::where('name', 'Haiti')->value('phonecode'))
                                ->preload()
                                ->searchable()
                                ->live()
                                ->required(fn (Get $get): bool => filled($get('phone_number')))
                                ->dehydrated(),

                            TextInput::make('phone_number')
                                ->label(__("myfinance.phone")),
                        ])->columnSpanFull()
                ])
            ])->columns(4);
    }
}
