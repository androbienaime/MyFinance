<?php

namespace App\Filament\Pages\Core;

use App\Actions\AccountSettlementAction;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AccountSettlement extends Page implements HasSchemas, HasTable
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::LockClosed;
    protected string $view = 'filament.pages.core.account-settlement';
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;

    use InteractsWithSchemas;
    use InteractsWithTable;
    use TransactionsTableTrait {
        TransactionsTableTrait::table insteadof InteractsWithTable;
    }

    /** Cache local pour eviter de re-interroger la DB a chaque render Livewire. */
    private array $accountCache = [];

    public static function getNavigationLabel(): string
    {
        return __('myfinance.account_settlement');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    protected function showTransferColumns(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('transactions.settlement') ?? false;
    }

    protected function transactionsTableScope($query): void
    {
        $query->whereIn('type', [
            TransactionType::Deposit,
            TransactionType::Withdrawal,
            TransactionType::AccountSettlement,
        ]);
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Point d'entree unique de resolution de compte, avec cache memoire
     * pour la duree de vie de l'instance (evite les requetes dupliquees
     * entre le afterStateUpdated et les prefixes de champs).
     */
    private function resolveAccount(?string $code): ?Account
    {
        if (blank($code)) {
            return null;
        }

        return $this->accountCache[$code] ??= Account::where('code', $code)
            ->with(['typeOfAccount', 'tagsPayments', 'customer.person', 'currency'])
            ->first();
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
                        ->live(debounce: 600)
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Reset systematique avant toute recherche, pour
                            // ne pas garder l'etat d'un compte precedent si
                            // le nouveau code est invalide/vide.
                            $set('active_case_payments', false);
                            $set('account_active', null);
                            $set('full_name', '');
                            $set('balance', '');
                            $set('references_people', '');
                            $set('fee_amount', '');
                            $set('fee_hint', '');
                            $set('prefix_field', '');

                            $this->resetErrorBag('data.full_name');

                            if (blank($state)) {
                                return;
                            }

                            $account = $this->resolveAccount($state);

                            if (! $account) {
                                if (strlen($state) > 4) {
                                    Notification::make()
                                        ->title('Aucun compte ne correspond a ce code.')
                                        ->warning()
                                        ->send();

                                    $this->addError('data.full_name', 'Compte Introuvable.');
                                }
                                return;
                            }

                            $accountCurrency = $account->currency;

                            $set('account_active', (bool) $account->is_active);
                            $set('full_name', $account->customer?->person?->full_name ?? 'Client inconnu');
                            $set('balance', (float) $account->balance);
                            $set('references_people', $account->getAccountInfos());
                            $set('prefix_field', $accountCurrency?->symbol ?? '');

                            if (! $account->is_active) {
                                Notification::make()
                                    ->title('Ce compte est desactive.')
                                    ->body('Aucun reglement ne peut etre enregistre tant que le compte n\'est pas reactive.')
                                    ->danger()
                                    ->send();

                                $this->addError('data.full_name', 'Compte desactive.');
                                return;
                            }

                            $feeAmount = $account->earlyWithdrawalFeeAmount();
                            $set('fee_amount', $feeAmount);
                            $set('fee_hint', $this->computeFeeHint($accountCurrency, $feeAmount));

                            $usesCases = (bool) $account->typeOfAccount->active_case_payments;

                            $set('active_case_payments', $usesCases);
                            $set('case_price', (float) $account->typeOfAccount->price);
                            $set('case_duration', (int) $account->typeOfAccount->duration);
                            $set('paid_tags', $account->tagsPayments->pluck('tags')->values()->all());
                            $set('tags', []);
                        }),

                    TextInput::make('full_name')
                        ->label(__('myfinance.account_holder'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null),

                    Textarea::make('references_people')
                        ->label(__('myfinance.people_associated'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null)
                        ->columnSpanFull(),

                    TextInput::make('balance')
                        ->label(__('myfinance.current_balance'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('balance') ?? 0), 2))
                        ->visible(fn (Get $get) => $get('account_active') ?? false)
                        ->prefix(fn (Get $get) => $get('prefix_field') ?: '')
                        ->columnSpanFull(),

                    TextInput::make('fee_amount')
                        ->label(__('myfinance.fee_amount'))
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix(fn (Get $get) => $get('prefix_field') ?: '')
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('fee_amount') ?? 0), 2))
                        ->hint(fn (Get $get) => $get('account_active') === false
                            ? 'Inactif'
                            : ($get('fee_hint') ?: null))
                        ->hintColor(fn (Get $get) => $get('account_active') === false ? 'danger' : 'gray')
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

    /**
     * Calcule le hint de conversion du frais si le compte de frais
     * configure a une devise differente de celle du compte regle.
     * Retourne une chaine vide si aucune conversion n'est necessaire,
     * ou si le compte de frais n'est pas trouve/configure (le hint
     * n'est qu'informatif, l'Action fera sa propre verification
     * bloquante au submit).
     */
    private function computeFeeHint($accountCurrency, float $feeAmount): string
    {
        if ($feeAmount <= 0 || ! $accountCurrency) {
            return '';
        }

        $feesAccountCode = setting('financial.fees_account_code');

        if (blank($feesAccountCode)) {
            return '';
        }

        $feesAccount = $this->resolveAccount($feesAccountCode);

        if (! $feesAccount || ! $feesAccount->currency) {
            return '';
        }

        $feesCurrency = $feesAccount->currency;

        if ($accountCurrency->is($feesCurrency)) {
            return '';
        }

        $convertedFee = $accountCurrency->convertTo($feeAmount, $feesCurrency);

        return sprintf(
            '≈ %s crediteront le compte de frais (taux: 1 %s = %s %s)',
            $feesCurrency->format($convertedFee),
            $accountCurrency->code,
            number_format($accountCurrency->rateTo($feesCurrency), 4),
            $feesCurrency->code,
        );
    }

    public function submitTransaction(): void
    {
        $state = $this->form->getState();
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()->title('Aucune fiche employe associee a votre compte.')->danger()->send();
            return;
        }

        if (! Auth::user()->can('transactions.settlement')) {
            Notification::make()->title('Vous n\'avez pas le droit d\'effectuer un reglement de compte.')->danger()->send();
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
            Notification::make()->title('Ce compte est desactive, reglement refuse.')->danger()->send();
            return;
        }

        try {
            $transaction = app(AccountSettlementAction::class)->handle(
                $state['account_code'],
                $employee,
            );

            Notification::make()->title("Reglement {$transaction->code} enregistre.")->success()->send();

            $this->accountCache = [];
            $this->form->fill();

            // active_case_payments / account_active / case_price /
            // case_duration / paid_tags ne sont PAS des champs declares du
            // formulaire - form->fill() ne les remet pas a zero.
            $this->data['active_case_payments'] = false;
            $this->data['account_active'] = null;
            $this->data['case_price'] = 0;
            $this->data['case_duration'] = 0;
            $this->data['paid_tags'] = [];

            $this->resetTable();

            $this->dispatch('deposit-saved');
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}