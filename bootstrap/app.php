<?php

use App\Exceptions\ProtectedDeletionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'customer.active' => \App\Http\Middleware\EnsureCustomerAccountActive::class,
            'merchant.active' => \App\Http\Middleware\EnsureMerchantAccountActive::class,
            'merchant.api_key' => \App\Http\Middleware\AuthenticateMerchantApiKey::class,
            'idempotency' => \App\Http\Middleware\EnsureIdempotentRequest::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
         $exceptions->render(function (ProtectedDeletionException $e, $request) {
        if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 409); // Conflict
            }

            return back()->with('error', $e->getMessage());
        });
    })->create();
