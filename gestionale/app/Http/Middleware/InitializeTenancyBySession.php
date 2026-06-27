<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = session('current_tenant_id');

        if (! $tenantId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nessun tenant selezionato'], 403);
            }
            return redirect('/');
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            session()->forget('current_tenant_id');
            return redirect('/')->withErrors(['tenant' => 'Attività non trovata.']);
        }

        // Initialize tenancy (switches DB connection to tenant database)
        tenancy()->initialize($tenant);

        $response = $next($request);

        // End tenancy after response
        tenancy()->end();

        return $response;
    }
}
