<?php

namespace App\Actions;

use App\Exceptions\CaisseSessionException;
use App\Models\Core\CaisseSession;
use App\Models\Core\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpenCaisseSessionAction
{
    /**
     * @param  array<int, float>  $declaredByCurrencyId
     */
    public function handle(Employee $employee, array $declaredByCurrencyId, ?Carbon $date = null): CaisseSession
    {
        return DB::transaction(function () use ($employee, $declaredByCurrencyId, $date) {
            if (! $employee->is_active) {
                throw new CaisseSessionException('Cet employe n\'est pas actif.');
            }

            foreach ($declaredByCurrencyId as $amount) {
                if ($amount < 0) {
                    throw new CaisseSessionException('Un montant declare ne peut pas etre negatif.');
                }
            }

            return CaisseSession::openFor($employee, $declaredByCurrencyId, $date);
        });
    }
}