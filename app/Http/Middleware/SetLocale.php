<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for custom 'Lang' header or fallback to 'Accept-Language'
        $language = $request->header('Accept-Language', 'en');

        // Set the application's locale
        app()->setLocale($language);


        return $next($request);
    }
}
