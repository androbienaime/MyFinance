<?php

use App\Services\SettingsManager;

if (! function_exists('setting')) {
    /**
     * Recupere la valeur d'un parametre dynamique (table settings),
     * avec resolution en cascade branche -> global -> defaut.
     */
    function setting(string $key, ?int $branchId = null, mixed $default = null): mixed
    {
        return app(\App\Services\SettingsManager::class)->get($key, $branchId, $default);
    }
}

if (! function_exists('set_setting')) {
    /**
     * Ecrit la valeur d'un parametre dynamique en base.
     */
    function set_setting(string $key, mixed $value, ?int $branchId = null): void
    {
        app(SettingsManager::class)->set($key, $value, $branchId);
    }
}