<?php

// app/Http/Middleware/NoIndex.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoIndex
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        return $response;
    }
}