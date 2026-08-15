<?php

// routes/api.php
use App\Http\Controllers\Api\Customer\AccountController;
use App\Http\Controllers\Api\Customer\AuthController;
use App\Http\Controllers\Api\Merchant\AuthController as MerchantAuthController;
use App\Http\Controllers\Api\Customer\NotificationController;
use App\Http\Controllers\Api\Merchant\ApiKeyController;
use App\Http\Controllers\Api\Merchant\ProfileController;
use App\Http\Controllers\Api\Merchant\SalesController;
use App\Http\Controllers\Api\P2pTransferController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Merchant\QrPaymentController as MerchantQrPaymentController;
use App\Http\Controllers\Api\Customer\QrPaymentController as CustomerQrPaymentController;


Route::prefix('customer')->group(function () {
    // Public - avant authentification
    Route::post('/activate', [AuthController::class, 'activate'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    // Protege - guard customer + verification du statut actif
    Route::middleware(['auth:customer', 'customer.active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);

        Route::get('/accounts', [AccountController::class, 'index']);
        Route::get('/accounts/{accountCode}/transactions', [AccountController::class, 'transactions']);

        Route::prefix('p2p-transfers')->group(function () {
            // Route::post('/initiate', [P2pTransferController::class, 'initiate'])->middleware('throttle:10,1');
            // Route::post('/confirm', [P2pTransferController::class, 'confirm'])->middleware('throttle:10,1');
        });

        Route::prefix('qr-payments')->group(function () {
            Route::get('/{reference}', [CustomerQrPaymentController::class, 'show']);
            Route::post('/{reference}/pay', [CustomerQrPaymentController::class, 'pay'])->middleware('throttle:10,1');
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        });
    });
});


Route::prefix('merchant')->group(function () {
    Route::post('/login', [MerchantAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:merchant', 'merchant.active'])->group(function () {
        Route::post('/logout', [MerchantAuthController::class, 'logout']);
        Route::post('/logout-all', [MerchantAuthController::class, 'logoutAllDevices']);
        Route::get('/profile', [ProfileController::class, 'show']);

        Route::prefix('sales')->group(function () {
            Route::get('/', [SalesController::class, 'index']);
            Route::get('/summary', [SalesController::class, 'summary']);
        });

        Route::prefix('api-keys')->group(function () {
            Route::get('/', [ApiKeyController::class, 'index']);
            Route::post('/', [ApiKeyController::class, 'store']);
            Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy']);
        });
    });
});

// --- API de paiement publique (POS, integrations tierces, cle API) ---
// Reservee pour le moteur QR - vide pour l'instant, comme convenu.
Route::prefix('payments/v1')->middleware(['merchant.api_key', 'throttle:merchant-api'])->group(function () {
    Route::post('/qr-payments', [MerchantQrPaymentController::class, 'store'])
    ->middleware('idempotency'); // alias a creer pour EnsureIdempotentRequest

    Route::get('/qr-payments/{reference}', [MerchantQrPaymentController::class, 'show']);
    Route::post('/qr-payments/{reference}/cancel', [MerchantQrPaymentController::class, 'cancel']);

});