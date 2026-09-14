<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TenantDomain;
use Illuminate\Support\Facades\App;

use Illuminate\Support\Facades\Log;
use App\Models\Domain;

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
        Log::info("IdentifyTenant Middleware: Incoming request for host: {$host}");
        
        // Find the domain in the database
        $tenantDomain = Domain::where('domain', $host)->with('tenant')->first();

        if (!$tenantDomain) {
            Log::warning("IdentifyTenant Middleware: No domain record found for host: {$host}");
            abort(404, 'Tenant not found.');
        }

        if (!$tenantDomain->tenant) {
            Log::warning("IdentifyTenant Middleware: Domain found but no associated tenant for host: {$host}");
            abort(404, 'Tenant not found.');
        }

        if ($tenantDomain->tenant->status !== 'active') {
            Log::warning("IdentifyTenant Middleware: Tenant found but status is not active (Status: {$tenantDomain->tenant->status}) for host: {$host}");
            // Depending on requirements, might want to still allow it or show a specific "Provisioning" page
            // abort(404, 'Tenant inactive.');
        }

        Log::info("IdentifyTenant Middleware: Successfully identified Tenant ID: {$tenantDomain->tenant->id} for host: {$host}");

        // Bind the tenant to the service container so it can be accessed globally
        App::instance('tenant', $tenantDomain->tenant);
        App::instance('tenant_id', $tenantDomain->tenant->id);

        return $next($request);
    }
}
