<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies PRD §24 session durations per role: staff 8h, patients 30d.
 * Hospital settings override config defaults (admin settings UI).
 */
class SetSessionLifetime
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $lifetimes = config('session.lifetimes', []);
            $key = $user->isStaff() ? Setting::SESSION_LIFETIME_STAFF : Setting::SESSION_LIFETIME_PATIENT;
            $fallback = $user->isStaff() ? ($lifetimes['staff'] ?? 480) : ($lifetimes['patient'] ?? 43200);

            $minutes = $user->hospital_id !== null
                ? (int) (Setting::get($user->hospital_id, $key) ?? $fallback)
                : (int) $fallback;

            config(['session.lifetime' => $minutes]);
        }

        return $next($request);
    }
}
