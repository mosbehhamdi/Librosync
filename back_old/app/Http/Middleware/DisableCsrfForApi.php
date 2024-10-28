<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableCsrfForApi
{
    public function handle(Request $request, Closure $next)
    {
        if (str_starts_with($request->path(), 'api/')) {
            return $next($request);
        }

        return app(\App\Http\Middleware\VerifyCsrfToken::class)->handle($request, $next);
    }
}
