<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Modules\Ecommerce\Models\StorefrontLayout;
use Modules\Ecommerce\Support\EcommerceClientContext;

class PageController extends Controller
{
    /**
     * Show a static storefront page.
     */
    public function show(string $page)
    {
        // Resolve company from storefront middleware attribute first,
        // then fall back to admin auth for preview context.
        $company = request()->attributes->get('ecommerce_company');

        if (!$company) {
            $clientId = (int) app(EcommerceClientContext::class)->clientId();

            if ($clientId > 0) {
                $company = Company::find($clientId);
            }

            if (!$company) {
                $admin = auth('ecommerce_admin')->user();
                $company = $admin?->getCompany();
            }
        }

        if (!$company) {
            abort(404);
        }

        $isPreview = request()->boolean('preview') && \Illuminate\Support\Facades\Auth::guard('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);

        $store = $company->ecommerce_slug;

        $storefrontName = $company->company_name ?: ($layout['brand_name'] ?? 'Store');
        $logoUrl = !empty($layout['logo_path'])
            ? (str_starts_with($layout['logo_path'], 'Modules/') ? \Illuminate\Support\Facades\Vite::asset($layout['logo_path']) : asset('storage/'.$layout['logo_path']))
            : ($company->logoUrl() ?: asset('ecommerce/Nexora_Logo.png'));

        // Theme colors
        $primaryHex = $layout['primary_color'] ?? '#ff6b00';
        $primaryClean = ltrim($primaryHex, '#');
        if (strlen($primaryClean) === 3) $primaryClean = $primaryClean[0].$primaryClean[0].$primaryClean[1].$primaryClean[1].$primaryClean[2].$primaryClean[2];
        $primaryR = hexdec(substr($primaryClean, 0, 2));
        $primaryG = hexdec(substr($primaryClean, 2, 2));
        $primaryB = hexdec(substr($primaryClean, 4, 2));

        $accentHex = $layout['accent_color'] ?? '#f59e0b';
        $accentClean = ltrim($accentHex, '#');
        if (strlen($accentClean) === 3) $accentClean = $accentClean[0].$accentClean[0].$accentClean[1].$accentClean[1].$accentClean[2].$accentClean[2];
        $accentR = hexdec(substr($accentClean, 0, 2));
        $accentG = hexdec(substr($accentClean, 2, 2));
        $accentB = hexdec(substr($accentClean, 4, 2));

        // Load page-specific content from support_pages or company_pages in layout
        $supportPages = $layout['support_pages'] ?? [];
        $companyPages = $layout['company_pages'] ?? [];
        $pageData = $supportPages[$page] ?? $companyPages[$page] ?? [];

        // Merge with defaults so any new keys (e.g. faq_items) are present
        // even if the saved layout still has the old structure.
        $defaults = StorefrontLayout::defaultFor($company);
        $defaultPageData = $defaults['support_pages'][$page] ?? $defaults['company_pages'][$page] ?? [];
        if (!empty($defaultPageData)) {
            $pageData = array_merge($defaultPageData, $pageData);
        }

        // Try supportpages first, then companypages
        $view = 'ecommerce::supportpages.' . $page;
        if (!view()->exists($view)) {
            $view = 'ecommerce::companypages.' . $page;
        }
        if (!view()->exists($view)) {
            abort(404);
        }

        return view($view, compact(
            'company', 'layout', 'store', 'storefrontName', 'logoUrl',
            'primaryHex', 'primaryR', 'primaryG', 'primaryB',
            'accentHex', 'accentR', 'accentG', 'accentB',
            'pageData',
        ));
    }
}
