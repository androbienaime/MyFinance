<?php
// app/Console/Commands/UpdateCurrencyRatesCommand.php
namespace App\Console\Commands;

use App\Services\CurrencyServices;
use Illuminate\Console\Command;

class UpdateCurrencyRatesCommand extends Command
{
    protected $signature = 'myfinance:update-currency-rates';
    protected $description = 'Met à jour les taux de change des devises via l\'API externe configurée';

    public function handle(CurrencyServices $currencyServices): int
    {
        $this->info('🔄 Mise à jour des taux de change...');

        $result = $currencyServices->updateRates();

        if ($result['error'] > 0) {
            $this->error("❌ Terminé avec erreur(s) : {$result['updated']} mise(s) à jour, {$result['error']} erreur(s). Voir les logs pour le détail.");

            return self::FAILURE;
        }

        $this->info("✅ {$result['updated']} devise(s) mise(s) à jour, aucune erreur.");

        return self::SUCCESS;
    }
}