<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TenantDomain;
use Illuminate\Support\Facades\App;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Find the domain in the database
        $tenantDomain = TenantDomain::where('domain', $host)->with('tenant')->first();

        if (!$tenantDomain || !$tenantDomain->tenant || $tenantDomain->tenant->status !== 'active') {
            abort(404, 'Tenant not found or inactive.');
        }

        // Bind the tenant to the service container so it can be accessed globally
        App::instance('tenant', $tenantDomain->tenant);
        App::instance('tenant_id', $tenantDomain->tenant->id);

        return $next($request);
    }
}
