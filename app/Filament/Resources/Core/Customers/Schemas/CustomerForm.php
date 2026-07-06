<?php

namespace App\Filament\Resources\Core\Customers\Schemas;

use App\Models\Core\City;
use App\Models\Core\Country;
use App\Models\Core\State;
use App\Models\Core\TypeOfAccount;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->visible(false),
                        TextInput::make('firstname')
                            ->required(),
                        TextInput::make('name')
                            ->required(),
                        Select::make('gender')
                            ->options([
                                'male' => 'Masculin',
                                'female' => 'Feminin',
                            ])
                        ->default(fn () => 'male'),
                        TextInput::make('identity_number')
                            ->label('Numero de piece d\'identite'),
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
                    ->columnSpan(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['address1'] ?? null),
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
