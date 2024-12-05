<?php

namespace Modules\Subscriber\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

class SubscriberMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (int) Auth::user()->role === User::ROLE_SUBSCRIBER) {
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Unauthorized access. Only Subscriber are allowed.'], 403);
    }
}
