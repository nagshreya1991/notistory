<?php

namespace Modules\Author\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

class AuthorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (int) Auth::user()->role === User::ROLE_AUTHOR) {
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Unauthorized access. Only Author are allowed.'], 403);
    }
}
