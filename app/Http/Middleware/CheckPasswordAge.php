<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MessageController;

/**
 * CheckPasswordAge Middleware
 *
 * Checks if the logged-in user's password is older than 6 months
 * and sends a security message if so.
 *
 * Register in app/Http/Kernel.php under $middlewareGroups['web']:
 *     \App\Http\Middleware\CheckPasswordAge::class,
 *
 * Or in a route group for authenticated routes.
 */
class CheckPasswordAge
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Only check once per session to avoid hitting DB every request
            $lastCheck = session('password_age_checked_at');

            if (!$lastCheck || now()->diffInHours($lastCheck) >= 24) {
                MessageController::checkPasswordAge(Auth::user()->user_id);
                session(['password_age_checked_at' => now()]);
            }
        }

        return $next($request);
    }
}
