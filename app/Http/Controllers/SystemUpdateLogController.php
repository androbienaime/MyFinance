<?php
namespace App\Http\Controllers;

class SystemUpdateLogController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()->can('system_updates.run'), 403);

        $logPath = storage_path('logs/myfinance-update.log');
        $lockPath = storage_path('logs/myfinance-update.lock');

        $content = file_exists($logPath) ? file_get_contents($logPath) : '';
        $running = file_exists($lockPath);

        // Le lock est retire par la commande elle-meme a la fin de son
        // execution - voir point 5 ci-dessous.

        return response()->json([
            'content' => $content,
            'running' => $running,
        ]);
    }
}