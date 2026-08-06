<?php

namespace App\Console\Commands;

use App\Enums\CaisseSessionStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Core\Branch;
use App\Models\Core\CaisseSession;
use App\Models\Core\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * A executer en fin de journee, juste avant reports:generate-daily-closing.
 * Repere les caisses ouvertes qui n'ont jamais ete fermees (caissier
 * absent, oubli...) et genere une alerte automatique par succursale,
 * pour qu'un superviseur puisse forcer la fermeture le lendemain matin.
 */
class FlagUnclosedCaisseSessionsCommand extends Command
{
    protected $signature = 'caisse:flag-unclosed {--date= : Date a verifier (Y-m-d), par defaut aujourd\'hui}';

    protected $description = 'Alerte les succursales ayant des caisses restees ouvertes en fin de journee';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $unclosed = CaisseSession::query()
            ->with(['employee', 'branch'])
            ->where('status', CaisseSessionStatus::Open->value)
            ->forDate($date)
            ->get()
            ->groupBy('branch_id');

        if ($unclosed->isEmpty()) {
            $this->info('Aucune caisse oubliee ouverte.');

            return self::SUCCESS;
        }

        foreach ($unclosed as $branchId => $sessions) {
            $branch = $sessions->first()->branch ?? Branch::find($branchId);

            Report::create([
                'type' => ReportType::Automatic,
                'category' => ReportCategory::Incident,
                'title' => "Caisses non fermees - {$branch?->name} - {$date->format('d/m/Y')}",
                'data' => [
                    'branch' => $branch?->name,
                    'session_date' => $date->toDateString(),
                    'unclosed_count' => $sessions->count(),
                    'employees' => $sessions->map(fn (CaisseSession $s) => $s->employee->full_name ?? $s->employee_id)->values()->all(),
                ],
                'employee_id' => null,
                'branch_id' => $branchId,
                'period_start' => $date->toDateString(),
                'period_end' => $date->toDateString(),
                'status' => ReportStatus::Pending,
            ]);

            $this->warn("{$sessions->count()} caisse(s) non fermee(s) signalee(s) pour {$branch?->name}");
        }

        return self::SUCCESS;
    }
}