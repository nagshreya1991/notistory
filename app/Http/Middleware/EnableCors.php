<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Set CORS headers
        $response->headers->set('Access-Control-Allow-Origin', '*'); // Use specific domain in production
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Socket-ID, X-HTTP-Method-Override');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('{"status": "OK"}', 200, $response->headers->all());
        }

        return $response;
    }
}
