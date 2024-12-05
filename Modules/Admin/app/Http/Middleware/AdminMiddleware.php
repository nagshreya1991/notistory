<?php

namespace Modules\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (int) Auth::user()->role === User::ROLE_ADMIN) {
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Unauthorized access. Only admins are allowed.'], 403);
    }
}
