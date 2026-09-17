<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Assuming tenant context is available via a middleware or route
        // E.g. $tenant = $request->tenant; or $tenant = app('tenant');
        $tenant = $request->user()?->tenant;
        
        if ($tenant && !$tenant->hasActiveSubscription()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscription expired or past due. Please renew to continue accessing this service.'
            ], 403);
        }

        return $next($request);
    }
}
