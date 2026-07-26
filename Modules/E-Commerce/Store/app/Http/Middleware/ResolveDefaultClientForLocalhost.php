<?php

namespace Modules\Ecommerce\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Ecommerce\Support\EcommerceClientContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveDefaultClientForLocalhost
{
    /**
     * For local development without subdomain DNS, resolve the first active
     * company that has an ecommerce_slug and use it as the storefront client.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = Company::query()
            ->where('status', 'Active')
            ->whereNotNull('ecommerce_slug')
            ->orderBy('id')
            ->first();

        if (! $company) {
            abort(404, 'No active company with an ecommerce_slug found. Seed a company first.');
        }

        app(EcommerceClientContext::class)->setClientId((int) $company->id);
        $request->attributes->set('ecommerce_company', $company);

        // Set default store parameter so route() calls work without {store}
        URL::defaults(['store' => $company->ecommerce_slug]);

        return $next($request);
    }
}
