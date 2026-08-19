<?php

use App\Http\Controllers\StatementController;
use App\Http\Controllers\SystemUpdateLogController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/statements/account/{account}/print', [StatementController::class, 'printAccount'])
        ->name('statements.account.print');

    Route::get('/statements/customer/{customer}/print', [StatementController::class, 'printCustomer'])
        ->name('statements.customer.print');

    Route::get('/statements/account/{account}/download', [StatementController::class, 'downloadAccountPdf'])
        ->name('statements.account.download');

    Route::get('/statements/customer/{customer}/download', [StatementController::class, 'downloadCustomerPdf'])
        ->name('statements.customer.download');

    Route::get('/system-updates/log', SystemUpdateLogController::class)
        ->name('system-updates.log');
});


require __DIR__.'/auth.php';
