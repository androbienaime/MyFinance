<?php

use App\Exceptions\ProtectedDeletionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
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
