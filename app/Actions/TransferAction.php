<?php

namespace App\Actions;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Customer;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use App\Services\TransferNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferAction
{
    public function __construct(private TransferNotifier $notifier) {}

    /**
     * Moteur UNIQUE de virement compte a compte. Utilise a la fois par
     * le guichet (employe, sans frais, sans OTP - identite deja
     * verifiee en personne) et par le P2P client (avec frais, appele
     * uniquement apres confirmation OTP par ConfirmP2pTransferAction).
     * Aucun autre point du code ne doit deplacer des fonds entre deux
     * comptes.
     *
     * @return Transaction[] Toutes les jambes creees (2 sans frais, 4 avec frais)
     */
    public function handle(
        string $fromAccountCode,
        string $toAccountCode,
        float $amount,
        ?Employee $employee = null,
        ?Customer $initiatingCustomer = null,
        float $feeAmount = 0.0,
    ): array {
        if ($fromAccountCode === $toAccountCode) {
            throw new TransactionRejectedException('Le compte source et destinataire ne peuvent pas etre identiques.');
        }

        if ($amount <= 0) {
            throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
        }

        return DB::transaction(function () use ($fromAccountCode, $toAccountCode, $amount, $employee, $initiatingCustomer, $feeAmount) {
            // Verrouille les deux comptes dans un ordre CONSTANT (id
            // croissant, jamais l'ordre source/destination fourni) pour
            // eviter un deadlock entre deux transferts inverses simultanes.
            $accounts = Account::whereIn('code', [$fromAccountCode, $toAccountCode])
                ->with('typeOfAccount', 'customer')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($account) => strtolower($account->code));

            $fromKey = strtolower($fromAccountCode);
            $toKey   = strtolower($toAccountCode);

            if (!$accounts->has($fromKey) || !$accounts->has($toKey)) {
                throw new TransactionRejectedException('Compte source ou destinataire introuvable.');
            }

            $from = $accounts->get($fromKey);
            $to   = $accounts->get($toKey);
            
            if (! $from->is_active) {
                throw new TransactionRejectedException('Le compte source est desactive.');
            }

            if (! $to->is_active) {
                throw new TransactionRejectedException('Le compte destinataire est desactive.');
            }

            if ((bool) $from->typeOfAccount->active_case_payments) {
                throw new TransactionRejectedException('Impossible de transferer depuis un compte a paiement par cases.');
            }

            if ((bool) $to->typeOfAccount->active_case_payments) {
                throw new TransactionRejectedException('Impossible de transferer vers un compte a paiement par cases.');
            }

            $totalDebit = $amount + $feeAmount;

            if ($totalDebit > $from->availableBalance()) {
                throw new TransactionRejectedException(
                    'Solde disponible insuffisant' . ($feeAmount > 0 ? ' (frais inclus).' : '.')
                );
            }

            $feesAccount = null;

            if ($feeAmount > 0) {
                $feesAccount = Account::where('code', config('myfinance.fees_account_code'))
                    ->lockForUpdate()
                    ->first();

                if (! $feesAccount) {
                    throw new TransactionRejectedException('Compte de frais introuvable - contactez un administrateur.');
                }
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Transfer, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $groupId = (string) Str::uuid();

            $common = [
                'transfer_group_id' => $groupId,
                'employee_id' => $employee?->id,
                'initiated_by_customer_id' => $initiatingCustomer?->id,
                'status' => $status,
            ];

            $debitPrincipal = Transaction::create($common + [
                'account_id' => $from->id,
                'counterparty_account_id' => $to->id,
                'direction' => TransactionDirection::Debit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'type' => TransactionType::Transfer,
            ]);

            $creditPrincipal = Transaction::create($common + [
                'account_id' => $to->id,
                'counterparty_account_id' => $from->id,
                'direction' => TransactionDirection::Credit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'type' => TransactionType::Transfer,
            ]);

            $legs = [$debitPrincipal, $creditPrincipal];

            if ($feeAmount > 0 && $feesAccount) {
                $legs[] = Transaction::create($common + [
                    'account_id' => $from->id,
                    'counterparty_account_id' => $feesAccount->id,
                    'direction' => TransactionDirection::Debit,
                    'code' => Transaction::generateUniqueCode(),
                    'amount' => $feeAmount,
                    'type' => TransactionType::TransferFee,
                ]);

                $legs[] = Transaction::create($common + [
                    'account_id' => $feesAccount->id,
                    'counterparty_account_id' => $from->id,
                    'direction' => TransactionDirection::Credit,
                    'code' => Transaction::generateUniqueCode(),
                    'amount' => $feeAmount,
                    'type' => TransactionType::TransferFee,
                ]);
            }

            if ($status === TransactionStatus::Completed) {
                $from->decrement('balance', $totalDebit);
                $to->increment('balance', $amount);
                $feesAccount?->increment('balance', $feeAmount);

                $this->notifier->notifyTransferCompleted($from, $to, $amount, $feeAmount);
            }

            return $legs;
        });
    }
}