<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\UpdateCurrencyRatesCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Signale les caisses oubliees ouvertes, juste avant la cloture journaliere
Schedule::command('caisse:flag-unclosed')->dailyAt('23:50');
Schedule::command('reports:generate-daily-closing')->dailyAt('23:55');
Schedule::command('reports:generate-monthly-summary')->monthlyOn(1, '01:00');



Schedule::command(UpdateCurrencyRatesCommand::class)
    ->dailyAt('06:00')
    ->timezone('America/Port-au-Prince')
    ->withoutOverlapping() // empeche un chevauchement si l'API met du temps a repondre un jour
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('security')
            ->warning('myfinance:update-currency-rates a échoué — vérifier les taux de change.');
    }); // optionnel, si tu as un email d'alerte configure