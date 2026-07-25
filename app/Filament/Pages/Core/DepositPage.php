<?php

namespace App\Filament\Pages\Core;

use App\Actions\DepositAction;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

class DepositPage extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;
    use TransactionsTableTrait {
        TransactionsTableTrait::table insteadof InteractsWithTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-circle';
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Depots';
    protected static ?string $title = 'Depots';
    protected string $view = 'filament.pages.deposit-page';

    public static function getNavigationLabel(): string
    {
        return __('myfinance.deposit');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    public static function getNavigationTitle(): string
    {
        return __('myfinance.deposits');
    }

    protected function transactionsTableScope($query): void
    {
        $query->where('type', TransactionType::Deposit)
            ->orWhere('type', TransactionType::Withdrawal)
            ->orWhere('type', TransactionType::AccountSettlement);
    }

    protected function showTransferColumns(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('transactions.deposit') ?? false;
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
                        ->label(__('myfinance.account_code'))
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
                        ->label(__('myfinance.account_holder'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null),
 
                    TextInput::make('balance')
                        ->label(__("myfinance.current_balance"))
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix('HTG')
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('balance') ?? 0), 2))
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->columnSpanFull(),
 
                    TextInput::make('amount')
                        ->label(__('myfinance.amount'))
                        ->numeric()
                        ->minValue(1)
                        ->required(fn (Get $get) => ! $get('active_case_payments'))
                        ->prefix('HTG')
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            // Affichage pur JS, jamais notifie a $wire - voir
                            // pourquoi dans le commentaire plus bas. Un 2e
                            // listener remet le champ a vide apres un
                            // enregistrement reussi (evenement dispatche par
                            // submitTransaction() uniquement en cas de succes
                            // reel, jamais sur un echec/rejet).
                            'x-on:case-total-updated.window' => '$el.value = $event.detail.total',
                            'x-on:deposit-saved.window' => '$el.value = \'\'',
                        ])
                        // Impossible de saisir un montant sur un compte
                        // desactive. Pour un compte a cases, le champ reste
                        // actif : il sert (1) d'affichage en lecture reactive
                        // du total des cases cochees (mis a jour par Alpine
                        // via l'evenement navigateur "case-total-updated",
                        // sans jamais notifier $wire pour ne pas bloquer la
                        // saisie manuelle), et (2) de champ de saisie du
                        // montant cible pour le bouton "Generer" ci-dessous.
                        // Le montant final n'est de toute facon JAMAIS pris
                        // depuis ce champ pour un compte a cases : voir
                        // submitTransaction().
                        ->disabled(fn (Get $get) => $get('account_active') === false)
                        ->suffixAction(
                            ActionsAction::make('generateCases')
                                ->label('Generer')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->visible(fn (Get $get) => $get('active_case_payments')
                                    && $get('account_active') !== false)
                                ->action(function (Get $get, $livewire) {
                                    // Dispatche un evenement navigateur global,
                                    // capte par Alpine (x-on:generate-cases.window)
                                    // dans case-grid.blade.php, qui vit dans un
                                    // autre scope Alpine (ViewField 'tags').
                                    $livewire->dispatch(
                                        'generate-cases',
                                        amount: (float) ($get('amount') ?? 0),
                                    );
                                }),
                        ),
                    ]),

            ViewField::make('tags')
                ->label('Cases a payer')
                ->view('filament.forms.components.case-grid')
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

        if (! Auth::user()->can('createDeposit', Transaction::class)) {
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
            $transaction = app(DepositAction::class)->handle(
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

    private function getCasePrice(string $accountCode): ?float
    {
        return Account::where('code', $accountCode)->first()?->typeOfAccount?->price;
    }
}