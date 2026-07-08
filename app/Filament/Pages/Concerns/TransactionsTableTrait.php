<?php

namespace App\Filament\Pages\Concerns;

use App\Actions\ApproveTransactionAction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
                TextColumn::make('code')->label('Code')->searchable()->copyable(),
                TextColumn::make('account.code')->label('Compte')->searchable(),

                TextColumn::make('account.customer.person.first_name')
                    ->label('Client')
                    ->formatStateUsing(fn ($record) => $record->account?->customer?->person
                        ? "{$record->account->customer->person->first_name} {$record->account->customer->person->last_name}"
                        : '-'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->type->label()),
                    // ->color(fn ($record) => $record->type->color()),

                TextColumn::make('amount')->label('Montant')->money('HTG')->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status->label()),
                    // ->color(fn ($record) => $record->status->color()),

                TextColumn::make('employee.fullName')
                    ->label('Employe')
                    ->getStateUsing(fn ($record) => $record->employee?->fullName()),

                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(TransactionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
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
            ])
            ->defaultSort('created_at', 'desc');
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
}