<?php

namespace App\Filament\Pages\Concerns;

use App\Actions\ApproveTransactionAction;
use App\Actions\DeleteTransactionAction;
use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Core\TransferPage;
use App\Models\Core\Transaction;
use App\Notifications\TransactionConfirmed;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Partagee par DepositPage/WithdrawPage/PaymentPage : le tableau et les
 * actions d'approbation sont identiques quelle que soit la page, seul
 * le formulaire du haut change.
 */
trait TransactionsTableTrait
{
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $employee = Auth::user()->employee;
                $query = Transaction::query();

                if ($employee) {
                    $query->orderByRelevanceTo($employee->branch_id, $employee->id);
                }

                return $query;
            })
            ->columns([
                // TextColumn::make('code')->label('Code')->searchable()->copyable(),
                TextColumn::make('account.code')->label(__('myfinance.account'))->searchable(),

                TextColumn::make('account.customer.person.full_name')
                    ->label(__('myfinance.customer'))
                    ->formatStateUsing(fn ($record) => $record->account?->customer?->person
                        ? "{$record->account->customer->person->first_name} {$record->account->customer->person->last_name}"
                        : '-'),

                TextColumn::make('type')
                    ->label(__('myfinance.type'))
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->type->label()),
                    // ->color(fn ($record) => $record->type->color()),

                TextColumn::make('counterpartyAccount.code')
                ->label('Compte lie')
                ->placeholder('—')
                ->description(fn ($record) => $record?->counterpartyAccount?->customer?->person?->full_name)
                ->toggleable()
                ->visible($this->showTransferColumns()),

                TextColumn::make('amount')->label(__('myfinance.amount'))->money('HTG')->sortable(),
                TextColumn::make('tagsPayments')
                ->label('Cases')
                ->badge()
                ->separator(',')
                ->getStateUsing(function ($record) {
                    $numbers = $record->tagsPayments
                        ->pluck('tags')
                        ->flatten()
                        ->filter(fn ($n) => is_numeric($n))
                        ->map(fn ($n) => (int) $n)
                        ->unique()
                        ->values();
                
                    return implode(', ', Transaction::compressTagsToRanges($numbers));
                })
                ->wrap()
                ->extraAttributes(['class' => 'min-w-[180px]'])
                ->color('info')
                ->visible(!$this->showTransferColumns()),

                TextColumn::make('direction')
                ->label('Sens')
                ->badge()
                ->color(fn (?TransactionDirection $state) => match ($state) {
                    TransactionDirection::Debit => 'danger',
                    TransactionDirection::Credit => 'success',
                    null => 'gray',
                })
                ->formatStateUsing(fn (?TransactionDirection $state) => match ($state) {
                    TransactionDirection::Debit => 'Sortant',
                    TransactionDirection::Credit => 'Entrant',
                    null => '—',
                })
                ->visible($this->showTransferColumns()),
                
                TextColumn::make('status')
                    ->label(__('myfinance.status'))
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status->label()),
                    // ->color(fn ($record) => $record->status->color()),

                TextColumn::make('employee.fullName')
                    ->label(__('myfinance.employee'))
                    ->getStateUsing(fn ($record) => $record->employee?->fullName()),

                TextColumn::make('transfer_group_id')
                    ->label('Groupe de virement')
                    ->copyable()
                    ->limit(8) // affiche juste le debut de l'UUID, suffisant pour reperer visuellement le lien entre les 2-4 jambes
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')->label(__('myfinance.date'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('myfinance.status'))
                    ->options(collect(TransactionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            
                SelectFilter::make('type')
                ->label('Type de transaction')
                ->options(collect(TransactionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('myfinance.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === TransactionStatus::Pending)
                    ->authorize(fn ($record) => Auth::user()->can('approve', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('comment')->label('Commentaire (optionnel)')])
                    ->action(function ($record, array $data) {
                        $this->runApproval(fn () => app(ApproveTransactionAction::class)->approve(
                            $record, Auth::user(), $data['comment'] ?? null
                        ), 'Transaction approuvee.');
                    }),

                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === TransactionStatus::Pending)
                    ->authorize(fn ($record) => Auth::user()->can('approve', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('comment')->label('Motif du rejet')->required()])
                    ->action(function ($record, array $data) {
                        $this->runApproval(fn () => app(ApproveTransactionAction::class)->reject(
                            $record, Auth::user(), $data['comment']
                        ), 'Transaction rejetee.');
                    }),

                Action::make('delete')
                ->label(__('myfinance.delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->authorize(fn ($record) => Auth::user()->can('delete', $record))
                ->requiresConfirmation()
                ->modalDescription('Cette action annule le mouvement de solde si la transaction etait completee, et libere les cases associees si applicable. Cette action est tracee et irreversible.')
                ->schema([
                    Textarea::make('reason')
                        ->label('Motif de la suppression')
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    try {
                        app(DeleteTransactionAction::class)->handle($record, Auth::user()->employee, $data['reason']);
                        Notification::make()->title('Transaction supprimee et solde ajuste.')->success()->send();
                    } catch (TransactionRejectedException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            // Historique des approbations - repond a "qui l'a approuve"
            Action::make('viewApprovals')
                ->label(__('myfinance.historical'))
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('Historique des decisions')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('myfinance.close'))
                ->schema(fn ($record) => $record->approvals->map(fn ($approval) =>
                    Placeholder::make("approval_{$approval->id}")
                        ->label("Niveau {$approval->level} — {$approval->decision}")
                        ->content(
                            ($approval->approver?->name ?? 'Utilisateur supprime')
                            . ' — ' . $approval->created_at->format('d/m/Y H:i')
                            . ($approval->comment ? " — \"{$approval->comment}\"" : '')
                        )->disabled()
                )->all() ?: [
                    Placeholder::make('none')->label('')->disabled()->content('Aucune approbation enregistree pour cette transaction.'),
                ]),
                
                Action::make('resend_whatsapp')
                ->label('Renvoyer par WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->action(function (Transaction $record) {
                    $record->account->customer->notify(new TransactionConfirmed($record));
                    Notification::make()
                        ->title('Message WhatsApp envoyé')
                        ->success()
                        ->send()
                        ->sendToDatabase(Auth::user()->employee);
                })
            ])
            ->query(function () {
                $employee = Auth::user()->employee;
                $query = Transaction::query();

                if ($employee) {
                    $query->orderByRelevanceTo($employee->branch_id, $employee->id);
                }

                // Point d'extension : chaque page (TransferPage, DepositPage, ...)
                // peut restreindre le scope en definissant sa propre methode
                // transactionsTableScope(). Par defaut aucun filtre supplementaire.
                if (method_exists($this, 'transactionsTableScope')) {
                    $this->transactionsTableScope($query);
                }

                return $query;
            })
            ->defaultSort('updated_at', 'desc');
    }

    private function runApproval(callable $callback, string $successMessage): void
    {
        try {
            $callback();
            Notification::make()->title($successMessage)->success()->send();
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function showTransferColumns(): bool
    {
        return true;
    }
}