<?php

namespace App\Actions;

use App\Enums\CaisseSessionStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Exceptions\CaisseSessionException;
use App\Models\Core\CaisseSession;
use App\Models\Core\CaisseSessionBalance;
use App\Models\Core\Report;
use Illuminate\Support\Facades\DB;

class CloseCaisseSessionAction
{
    /**
     * @param  array<int, array{declared: float, comment?: string|null}>  $closingData
     */
    public function handle(CaisseSession $session, array $closingData): CaisseSession
    {
        return DB::transaction(function () use ($session, $closingData) {
            if ($session->status !== CaisseSessionStatus::Open) {
                throw new CaisseSessionException('Cette session de caisse n\'est pas ouverte.');
            }

            foreach ($closingData as $entry) {
                if ((float) $entry['declared'] < 0) {
                    throw new CaisseSessionException('Un montant declare ne peut pas etre negatif.');
                }
            }

            $session->closeWith($closingData);
            $session->refresh()->load('balances.currency');

            foreach ($session->discrepantBalances() as $balance) {
                $this->createDiscrepancyReport($session, $balance);
            }

            return $session;
        });
    }

    /**
     * Un rapport d'ecart automatique, genere separement pour chaque devise
     * en ecart — un caissier peut etre juste en HTG mais en manque en USD,
     * et les deux ne doivent pas se compenser dans un seul rapport.
     */
    protected function createDiscrepancyReport(CaisseSession $session, CaisseSessionBalance $balance): Report
    {
        $sign = $balance->closing_discrepancy > 0 ? 'excedent' : 'manquant';
        $currencyCode = $balance->currency->iso_code;

        return Report::create([
            'type' => ReportType::Automatic,
            'category' => ReportCategory::CashDiscrepancy,
            'title' => sprintf(
                'Ecart de caisse (%s %s) - %s - %s',
                $sign,
                $currencyCode,
                $session->employee->full_name ?? $session->employee_id,
                $session->session_date->format('d/m/Y')
            ),
            'data' => [
                'employee' => $session->employee->full_name ?? null,
                'branch' => $session->branch->name ?? null,
                'session_date' => $session->session_date->toDateString(),
                'currency' => $currencyCode,
                'opening_balance_declared' => (float) $balance->opening_balance_declared,
                'opening_balance_expected' => (float) $balance->opening_balance_expected,
                'total_deposits' => (float) $balance->total_deposits,
                'total_withdrawals' => (float) $balance->total_withdrawals,
                'total_other_movements' => (float) $balance->total_other_movements,
                'closing_balance_expected' => (float) $balance->closing_balance_expected,
                'closing_balance_declared' => (float) $balance->closing_balance_declared,
                'closing_discrepancy' => (float) $balance->closing_discrepancy,
                'closing_comment' => $balance->closing_comment,
            ],
            'employee_id' => $session->employee_id,
            'branch_id' => $session->branch_id,
            'period_start' => $session->session_date,
            'period_end' => $session->session_date,
            'status' => ReportStatus::Pending,
        ]);
    }
}