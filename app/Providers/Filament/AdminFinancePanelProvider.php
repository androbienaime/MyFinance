<?php

namespace App\Providers\Filament;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminFinancePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('adminFinance')
            ->path('adminFinance')
            ->brandName("LTFINANCE")
            ->login()
            ->colors([
                'primary' => [
                    50  => '#eef8ff',
                    100 => '#d9efff',
                    200 => '#bce4ff',
                    300 => '#8fd3ff',
                    400 => '#57bbff',
                    500 => '#2196F3', // couleur principale
                    600 => '#1976D2',
                    700 => '#1565C0',
                    800 => '#0D47A1',
                    900 => '#082B66',
                    950 => '#051838',
                ],
                'secondary' => [
                    50  => '#f3f3f3',
                    100 => '#e0e0e0',
                    200 => '#c2c2c2',
                    300 => '#a3a3a3',
                    400 => '#858585',
                    500 => '#dbbb03', // couleur secondaire
                    600 => '#4d4d4d',
                    700 => '#333333',
                    800 => '#1a1a1a',
                    900 => '#0d0d0d',
                    950 => '#050505',
                ],
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(), // génère des codes de secours en cas de perte du téléphone
            ], isRequired: false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
