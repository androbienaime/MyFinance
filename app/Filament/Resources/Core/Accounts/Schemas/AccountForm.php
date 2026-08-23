<?php

namespace App\Filament\Resources\Core\Accounts\Schemas;

use App\Enums\AccountHolderType;
use App\Models\Core\AccountPerson;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Person;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->visible(false),

            TextInput::make('employee_id')
                ->numeric()
                ->default(fn () => auth()->user()->employee?->id)
                ->disabled()
                ->dehydrated()
                ->required()
                ->visible(false),

         
            Select::make('customer_id')
            ->label('Client')
            ->relationship(
                name: 'customer',
                modifyQueryUsing: fn (Builder $query) => $query->with(['person', 'person.identityDocuments']),
            )
            ->getOptionLabelFromRecordUsing(fn ($record) => static::formatCustomerLabel($record))
            ->searchable(['code']) // garde une recherche native de fallback sur 'code' ; le vrai filtrage se fait ci-dessous
            ->getSearchResultsUsing(function (string $search): array {
                return Customer::query()
                    ->with(['person', 'person.identityDocuments'])
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('person', function (Builder $query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereHas('identityDocuments', function (Builder $q) use ($search) {
                                $q->where('document_number', 'like', "%{$search}%");
                            });
                    })
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn ($customer) => [
                        $customer->id => static::formatCustomerLabel($customer),
                    ])
                    ->toArray();
            })
            ->disabled(fn (string $operation, $record) => $operation === 'edit' && $record && (float) $record->balance > 0)
            ->getOptionLabelUsing(function ($value): ?string {
                $customer = Customer::with(['person', 'person.identityDocuments'])->find($value);

                return $customer ? static::formatCustomerLabel($customer) : null;
            })
            ->preload()
            ->live()
            ->required(),

            Select::make('type_of_account_id')
                ->label('Type de compte')
                ->relationship(
                        name: 'typeOfAccount',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $get('holder_type') === AccountHolderType::Merchant->value
                            ? $query->where('active_case_payments', false)
                            : $query,
                    )
                ->disabled(fn (string $operation, $record) => $operation === 'edit' && $record && (float) $record->balance > 0)
                ->helperText(fn (string $operation, $record) => $operation === 'edit' && $record && (float) $record->balance > 0
                    ? '🔒 Verrouillé : ce compte a un solde positif (' . number_format($record->balance, 2) . ').'
                    : null)
                ->live()
                ->required(),

            Select::make('currency_id')
            ->label('Devise')
            ->relationship("currency", "name")
            ->default(fn ()=> Currency::where("iso_code", setting("financial.default_currency", default:'HTG'))->first()->id)
            ->getOptionLabelFromRecordUsing(
                fn ($record) => translate_currency_name($record->name)
            )
            ->disabled(fn (string $operation, $record) => $operation === 'edit' && $record && (float) $record->balance > 0)
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
                ->disabled(fn (string $operation, $record) => $operation === 'edit' && $record && (float) $record->balance > 0)
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
                ]),

            TextInput::make('balance')
                ->required()
                ->numeric()
                ->default(0.0)
                ->disabled()
                ->dehydrated()
                ->visible(false),

            Toggle::make('is_active')->default(true)->visible(false)->required(),

            // HasMany -> Filament sauvegarde automatiquement ces lignes
            // APRES la creation du compte (contrairement a Person/Customer
            // qui est BelongsTo et doit exister AVANT). Pas de logique
            // manuelle necessaire ici, contrairement a CreateCustomer.
            Repeater::make('accountPeople')
            ->relationship(
                'accountPeople',
                modifyQueryUsing: fn (Builder $query) => $query->where('role', '!=', 'owner')
            )
            ->label('')
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::persistAddressAndStrip($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::persistAddressAndStrip($data))
            ->schema([
                Grid::make(2)->schema([
                    Select::make('person_id')
                        ->label('Personne')
                        ->relationship('person', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn (Person $record) => trim("{$record->first_name} {$record->last_name}"))
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return Person::query()
                                ->where('employee_id', auth()->user()->employee?->id)
                                ->where(function (Builder $query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Person $person) => [
                                    $person->id => trim("{$person->first_name} {$person->last_name}"),
                                ]);
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $person = Person::find($value);

                            return $person ? trim("{$person->first_name} {$person->last_name}") : null;
                        })
                        ->preload()
                        ->live()
                        ->default(function (Get $get) {
                            $customerId = $get('../../customer_id');

                            if (! $customerId) {
                                return null;
                            }

                            return AccountPerson::whereHas(
                                    'account',
                                    fn (Builder $query) => $query->where('customer_id', $customerId)
                                )
                                ->latest('id')
                                ->value('person_id');
                        })
                        ->required()
                        ->createOptionForm([
                            Grid::make(2)->schema([
                                TextInput::make('first_name')->label('Prenom')->required(),
                                TextInput::make('last_name')->label('Nom')->required(),
                                Select::make('gender')
                                    ->label('Genre')
                                    ->options(['male' => 'Masculin', 'female' => 'Feminin']),
                            ]),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $data['employee_id'] = auth()->user()->employee?->id;

                            return Person::create($data)->getKey();
                        }),

                    Select::make('role')
                        ->label('Role')
                        ->live()
                        ->options([
                            'co_owner' => 'Cotitulaire',
                            'attorney' => 'Mandataire',
                            'beneficiary' => 'Beneficiaire',
                            'guardian' => 'Representant legal',
                        ])
                        ->default(fn () => 'attorney')
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('permissions', match ($state) {
                                'owner', 'co_owner' => ['view', 'withdraw', 'deposit'],
                                'attorney' => ['view', 'withdraw'],
                                default => ['view'],
                            });
                        }),

                    TextInput::make('share_percentage')
                        ->label('Part (%)')
                        ->numeric()
                        ->suffix('%')
                        ->visible(fn ($get) => $get('role') === 'beneficiary'),

                    Hidden::make('permissions')->default(['view']),
                ]),

                Section::make('Adresse')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('address.country_id')
                            ->label('Pays')
                            ->options(fn () => \App\Models\Core\Country::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'country_id')),

                        Select::make('address.state_id')
                            ->label('Departement/Etat')
                            ->options(function (Get $get) {
                                $countryId = $get('address.country_id');

                                return $countryId
                                    ? \App\Models\Core\State::where('country_id', $countryId)->pluck('name', 'id')
                                    : \App\Models\Core\State::pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'state_id')),

                        Select::make('address.city_id')
                            ->label('Ville')
                            ->options(function (Get $get) {
                                $stateId = $get('address.state_id');

                                return $stateId
                                    ? \App\Models\Core\City::where('state_id', $stateId)->pluck('name', 'id')
                                    : \App\Models\Core\City::pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'city_id')),
                        TextInput::make('address.city2')
                            ->label('Ville (complement)')
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'city2')),

                        TextInput::make('address.address1')
                            ->label('Adresse ligne 1')
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'address1')),

                        TextInput::make('address.address2')
                            ->label('Adresse ligne 2')
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'address2')),

                        TextInput::make('address.phone')
                            ->label('Telephone')
                            ->tel()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'phone')),

                        TextInput::make('address.email')
                            ->label('Email')
                            ->email()
                            ->afterStateHydrated(fn ($component, Get $get) => static::hydrateAddressField($component, $get, 'email')),
                    ]),
            ])
            ->addActionLabel('Ajouter une personne')
            ->collapsible()
            ->itemLabel(fn (array $state) => Person::find($state['person_id'] ?? null)?->first_name),
        ]);
    }


    protected static function formatCustomerLabel($record): string
    {
        $primaryDoc = $record->person?->identityDocuments
            ?->where('is_primary', true)
            ->first();

        $fallback = $record->email
            ?? $record->phone_number
            ?? ($primaryDoc ? "{$primaryDoc->document_type}:{$primaryDoc->document_number}" : null);

        return trim("{$record->person?->full_name} ({$fallback})");
    }


    /**
     * Recupere l'adresse active existante de la personne pour pre-remplir
     * le champ au chargement du formulaire (edition d'un accountPeople
     * deja en base) - les champs 'address.*' ne correspondent a aucune
     * colonne d'AccountPerson, donc Filament ne peut pas les hydrater
     * automatiquement via la relation.
     */
    protected static function hydrateAddressField($component, Get $get, string $field): void
    {
        $personId = $get('person_id');

        if (! $personId) {
            return;
        }

        $address = Person::find($personId)?->addresses()->where('active', true)->first();

        $component->state($address?->{$field});
    }

    /**
     * Cree ou met a jour l'adresse active de la personne comme effet de
     * bord, puis retire la cle 'address' du tableau - AccountPerson n'a
     * pas de colonne 'address', ce champ ne doit jamais lui etre transmis.
     */
    protected static function persistAddressAndStrip(array $data): array
    {
        $addressData = $data['address'] ?? null;
        unset($data['address']);

        if (! $addressData || ! array_filter($addressData) || empty($data['person_id'])) {
            return $data;
        }

        $person = Person::find($data['person_id']);

        if (! $person) {
            return $data;
        }

        $existing = $person->addresses()->where('active', true)->first();

        if ($existing) {
            $existing->update($addressData);
        } else {
            $addressData['active'] = true;
            $person->addresses()->create($addressData);
        }

        return $data;
    }
}