<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the current hospital id into the container so the
 * BelongsToHospital global scope can constrain tenant queries.
 */
class ResolveHospital
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        app()->instance(
            'currentHospitalId',
            $user?->hospital_id
        );

        return $next($request);
    }
}
