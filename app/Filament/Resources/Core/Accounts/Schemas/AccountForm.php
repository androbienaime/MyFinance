<?php

namespace App\Filament\Resources\Core\Accounts\Schemas;

use App\Models\Core\AccountPerson;
use App\Models\Core\Customer;
use App\Models\Core\Person;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    modifyQueryUsing: fn (Builder $query) => $query->with('person'),
                )
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => "{$record->person?->first_name} {$record->person?->last_name} ({$record->email})"
                )
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return Customer::query()
                        ->with('person')
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('person', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($customer) => [
                            $customer->id => "{$customer->person?->first_name} {$customer->person?->last_name} ({$customer->code})",
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $customer = Customer::with('person')->find($value);

                    return $customer
                        ? "{$customer->person?->first_name} {$customer->person?->last_name} ({$customer->code})"
                        : null;
                })
                ->preload()
                ->live()
                ->required(),

            Select::make('type_of_account_id')
                ->label('Type de compte')
                ->relationship('typeOfAccount', 'name')
                ->required(),

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
            Section::make('Personnes associees au compte')
                ->description('Choisis une personne existante ou cree-la a la volee.')
                ->schema([
                    Repeater::make('accountPeople')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('person_id')
                                    ->label('Personne')
                                    ->relationship('person', 'first_name') // pas de modifyQueryUsing ici -> validation "exists" reste correcte
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
                                        // 'owner' => 'Titulaire',
                                        'co_owner' => 'Cotitulaire',
                                        'attorney' => 'Mandataire',
                                        'beneficiary' => 'Beneficiaire',
                                        'guardian' => 'Representant legal',
                                    ])
                                    ->default(fn () => 'attorney')
                                    ->required()
                                    // Deduit des permissions par defaut selon
                                    // le role choisi - simple valeur de depart
                                    // stockee, pas encore verifiee par les
                                    // Actions (mis en pause volontairement).
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
                        ])
                        ->addActionLabel('Ajouter une personne')
                        ->collapsible()
                        ->itemLabel(fn (array $state) => Person::find($state['person_id'] ?? null)?->first_name),
                ]),
        ]);
    }
}