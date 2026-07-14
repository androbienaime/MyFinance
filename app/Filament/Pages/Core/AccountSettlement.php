<?php

namespace App\Filament\Pages\Core;

use App\Actions\AccountSettlementAction;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
use App\Models\Core\AccountPerson;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AccountSettlement extends Page implements HasSchemas, HasTable
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::LockClosed;
    protected string $view = 'filament.pages.core.account-settlement';
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;

     public static function getNavigationLabel(): string
    {
        return __('myfinance.account_settlement');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    use InteractsWithSchemas;
    use InteractsWithTable;
    use TransactionsTableTrait {
        TransactionsTableTrait::table insteadof InteractsWithTable;
    }


    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('account_code')
                        ->label(__("myfinance.account_code"))
                        ->required()
                        // debounce plutot que onBlur : se declenche des que
                        // l'utilisateur arrete de taper, sans avoir a sortir
                        // du champ.
                        ->live(debounce: 600)
                        // Charge les infos du type de compte des que le code
                        // est saisi : prix/case, duree (nombre de cases), et
                        // les cases deja payees pour griser la grille.
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Reset systematique avant toute recherche, pour
                            // ne pas garder l'etat d'un compte precedent si
                            // le nouveau code est invalide/vide.
                            $set('active_case_payments', false);
                            $set('account_active', null);
                            $set('full_name', '');
                            $set('balance', '');
                            $set('references_people', '');

                            // Toujours reset l'erreur avant toute nouvelle recherche
                            $this->resetErrorBag('data.full_name');

                            if (blank($state)) {
                                return;
                            }

                            $account = Account::where('code', $state)
                                ->with('typeOfAccount', 'tagsPayments', 'customer.person')
                                ->first();

                            if (! $account) {
                                if(strlen($state) > 4){
                                    Notification::make()
                                        ->title('Aucun compte ne correspond a ce code.')
                                        ->warning()
                                        ->send();

                                    $this->addError('data.full_name', 'Compte Introuvable.');

                                }
                                return;
                            }

                            $set('account_active', (bool) $account->is_active);
                            $set('full_name', $account->customer?->person?->full_name ?? 'Client inconnu');
                            $set('balance', (float) $account->balance);
                            $set('references_people', $this->getAccountInfos($account->accountPeople));

                            if (! $account->is_active) {
                                Notification::make()
                                    ->title('Ce compte est desactive.')
                                    ->body('Aucun depot ne peut etre enregistre tant que le compte n\'est pas reactive.')
                                    ->danger()
                                    ->send();

                                $this->addError('data.full_name', 'Compte desactive.');

                                return;
                            }

                            $usesCases = (bool) $account->typeOfAccount->active_case_payments;

                            $set('active_case_payments', $usesCases);
                            $set('case_price', (float) $account->typeOfAccount->price);
                            $set('case_duration', (int) $account->typeOfAccount->duration);
                            $set('paid_tags', $account->tagsPayments->pluck('tags')->values()->all());
                            $set('tags', []);
                        }),

                // officiel pour un champ "calcule / lecture seule" est un
                    // TextInput disabled() + dehydrated(false) + formatStateUsing()
                    // (jamais state(), qui sert a l'hydratation du modele, pas a
                    // l'affichage). hint()/hintColor()/helperText() sont l'API
                    // native de Filament pour un indicateur colore - garantis
                    // reactifs, contrairement au HTML brut via extraInputAttributes.
                    TextInput::make('full_name')
                        ->label(__("myfinance.account_holder"))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null),
                    
                    Textarea::make('references_people')
                        ->label(__("myfinance.people_associated"))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null)
                        ->columnSpanFull(),
 
                    TextInput::make('balance')
                        ->label(__("myfinance.current_balance"))
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix('HTG')
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('balance') ?? 0), 2))
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->columnSpanFull(),
                    ]),

            ViewField::make('tags')
                ->label('Cases a payer')
                ->view('filament.forms.components.case-grid')
                ->viewData(['readonly' => true])
                ->visible(fn ($get) => $get('active_case_payments')
                    && $get('account_active') !== false
                    && (int) $get('case_duration') > 0)
                ->default([]),
        ])->statePath('data');
    }

    public function submitTransaction(): void
    {
        $state = $this->form->getState();
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()->title('Aucune fiche employe associee a votre compte.')->danger()->send();
            return;
        }

        if (! Auth::user()->can('createWithdrawal', Transaction::class)) {
            Notification::make()->title('Vous n\'avez pas le droit d\'effectuer un depot.')->danger()->send();
            return;
        }

        // Re-verification cote serveur : le state du formulaire (account_active)
        // n'est qu'un affichage, on ne peut pas s'y fier pour la securite.
        $account = Account::where('code', $state['account_code'] ?? null)->first();

        if (! $account) {
            Notification::make()->title('Aucun compte ne correspond a ce code.')->danger()->send();
            return;
        }

        if (! $account->is_active) {
            Notification::make()->title('Ce compte est desactive, depot refuse.')->danger()->send();
            return;
        }

        // Point de securite : pour un compte a cases, le montant n'est
        // JAMAIS pris depuis $state['amount'] (calcul cote client, donc
        // manipulable) - il est toujours recalcule dans DepositAction, a
        // partir du prix reel de la case et du nombre de cases cochees.
        try {
            $transaction = app(AccountSettlementAction::class)->handle(
                $state['account_code'],
                (float) ($state['amount'] ?? 0), // ignore par l'Action si le compte utilise les cases
                $employee,
                ($state['tags'] ?? [])
            );

            Notification::make()->title("Depot {$transaction->code} enregistre.")->success()->send();

            $this->form->fill();

            // active_case_payments / account_active / case_price /
            // case_duration / paid_tags ne sont PAS des champs declares du
            // formulaire (juste des cles ecrites via $set() dans le
            // afterStateUpdated de account_code) - form->fill() ne les
            // remet pas a zero, contrairement aux vrais champs
            // (account_code, full_name, balance, amount, tags). Sans ce
            // reset manuel, visible() de la grille continuait de lire les
            // anciennes valeurs et la grille restait affichee.
            $this->data['active_case_payments'] = false;
            $this->data['account_active'] = null;
            $this->data['case_price'] = 0;
            $this->data['case_duration'] = 0;
            $this->data['paid_tags'] = [];

            $this->resetTable();

            // Uniquement en cas de succes reel : on demande a la grille et
            // au champ Montant de se reinitialiser visuellement tout de
            // suite (au lieu d'attendre le prochain re-render Livewire, qui
            // ne toucherait meme pas .value puisqu'on l'ecrit en JS pur).
            $this->dispatch('deposit-saved');
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }


    public function getAccountInfos(Collection|null $accountPeople){
        if($accountPeople === null){
            return "Aucune personne n'est associer a ce compte";
        }

        return $lines = $accountPeople
            ->values()
            ->map(function ($accountPerson, $index) {
                $person = $accountPerson->person;

                $document = $person->identityDocuments
                    ->sortByDesc('is_primary')
                    ->first();

                $line = ($index + 1) . ". {$person->full_name}";

                if ($document) {
                    $line .= " - {$document->document_type} : {$document->document_number}";
                }

                $permissions = implode(', ', $accountPerson->permissions ?? []);
                $line .= " - {$accountPerson->role} [{$permissions}]";

                // à adapter : quel(s) rôle(s) doivent afficher le %
                if ($accountPerson->role === 'attorney') {
                    $line .= " : ({$accountPerson->share_percentage}%)";
                }

                $line .= " {$accountPerson->end_date}";

                return $line;
            })
            ->implode("\n");
    }

}
