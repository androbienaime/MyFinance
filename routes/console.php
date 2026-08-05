<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Signale les caisses oubliees ouvertes, juste avant la cloture journaliere
Schedule::command('caisse:flag-unclosed')->dailyAt('23:50');
Schedule::command('reports:generate-daily-closing')->dailyAt('23:55');
Schedule::command('reports:generate-monthly-summary')->monthlyOn(1, '01:00');