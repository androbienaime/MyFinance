<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use Illuminate\Support\Facades\DB;

class DepositAction
{
    /**
     * @param  array<int, int>|null  $tagNumbers  Numeros de case, uniquement
     *     pertinent si le type de compte a active_case_payments = true.
     */
    public function handle(string $accountCode, float $amount, Employee $employee, ?array $tagNumbers = null): Transaction
    {
        return DB::transaction(function () use ($accountCode, $amount, $employee, $tagNumbers) {
            // Verrou pessimiste : bloque toute autre transaction concurrente
            // sur ce meme compte jusqu'a la fin de ce bloc - protege aussi
            // contre deux depots simultanes qui tenteraient de payer la
            // meme case en meme temps (voir assertValidTags ci-dessous).
            $account = Account::where('code', $accountCode)
                ->with('typeOfAccount')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            $usesCases = (bool) $account->typeOfAccount->active_case_payments;

            if ($usesCases) {
                // Le montant n'est JAMAIS pris depuis le parametre $amount
                // pour un compte a cases - meme si l'appelant (une Page
                // Filament aujourd'hui, une API demain) recalcule deja de
                // son cote, on ne lui fait pas confiance : c'est ici, dans
                // l'Action, que vit la garantie finale.
                $amount = $this->assertValidTagsAndComputeAmount($account, $tagNumbers ?? []);
            } else {
                if (! empty($tagNumbers)) {
                    throw new TransactionRejectedException('Ce compte n\'accepte pas de paiement par cases.');
                }

                if ($amount <= 0) {
                    throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
                }
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Deposit, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'employee_id' => $employee->id,
                'type' => TransactionType::Deposit,
                'status' => $status,
            ]);

            if ($usesCases) {
                // La contrainte unique (account_id, tag) en base (voir la
                // migration tags_payments) est le dernier filet de securite
                // si, malgre le verrou pessimiste ci-dessus, une case avait
                // deja ete prise entre-temps - cet insert echouerait alors
                // proprement avec une exception SQL plutot que de creer un
                // double paiement silencieux.
                $transaction->tagsPayments()->createMany(
                    collect($tagNumbers)->map(fn (int $tag) => [
                        'tags' => $tag,
                        'account_id' => $account->id,
                    ])->all()
                );
            }

            if ($status === TransactionStatus::Completed) {
                $account->increment('balance', $amount);
            }

            return $transaction;
        });
    }

    /**
     * @param  array<int, int>  $tagNumbers
     */
    private function assertValidTagsAndComputeAmount(Account $account, array $tagNumbers): float
    {
        if (empty($tagNumbers)) {
            throw new TransactionRejectedException('Vous devez selectionner au moins une case.');
        }

        // Pas de doublon dans la selection elle-meme (ex: payload manipule
        // envoyant deux fois le meme numero).
        if (count($tagNumbers) !== count(array_unique($tagNumbers))) {
            throw new TransactionRejectedException('La selection contient des cases en double.');
        }

        $maxCase = (int) $account->typeOfAccount->duration * 30;

        foreach ($tagNumbers as $tag) {
            if ($tag < 1 || $tag > $maxCase) {
                throw new TransactionRejectedException("La case {$tag} est hors des limites autorisees (1 a {$maxCase}).");
            }
        }

        // Recharge les cases deja payees DIRECTEMENT depuis la base, dans
        // cette meme transaction verrouillee - jamais depuis une liste
        // fournie par l'appelant, qui pourrait etre perimee ou manipulee.
        $alreadyPaid = $account->tagsPayments()->pluck('tags')->all();
        $duplicates = array_intersect($alreadyPaid, $tagNumbers);

        if (! empty($duplicates)) {
            throw new TransactionRejectedException(
                'Les cases suivantes ont deja ete payees : '.implode(', ', $duplicates)
            );
        }

        // Le vrai calcul du montant : progressif, numero_de_case * prix,
        // somme sur toutes les cases choisies (voir la discussion sur le
        // modele de tarification du "tanti").
        return collect($tagNumbers)->sum() * (float) $account->typeOfAccount->price;
    }
}