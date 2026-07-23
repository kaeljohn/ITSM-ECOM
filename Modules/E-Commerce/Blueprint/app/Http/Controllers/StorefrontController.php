<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Models\Company;
use Modules\Ecommerce\Models\CustombuiltConfig;
use Modules\Ecommerce\Models\PrebuiltConfig;
use Modules\Ecommerce\Models\StorefrontLayout;
use Modules\Ecommerce\Models\StorefrontListing;

class StorefrontController extends Controller
{
    public function index()
    {
        /** @var Company $company */
        $company = request()->attributes->get('ecommerce_company');
        $isPreview = request()->boolean('preview') && \Illuminate\Support\Facades\Auth::guard('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);
        
        $hero = collect($layout['sections'] ?? [])->firstWhere('id', 'hero');
        $featuredConfigIds = $hero['featured_configs'] ?? [];
        
        if (!empty($featuredConfigIds)) {
            $customConfigs = collect([]);
        } else {
            $customConfigs = collect([]);
        }

        $storefrontListings = StorefrontListing::query()->where('is_active', true)->latest()->get();
        $allListings = StorefrontListing::query()->get()->keyBy('id');
        
        return view('ecommerce::storefront', [
            'company' => $company,
            'layout' => $layout,
            'storefrontListings' => $storefrontListings->take(12),
            'tierListings' => $storefrontListings->take(4),
            'prebuiltListings' => $storefrontListings->skip(4)->take(8),
            'allListings' => $allListings,
            'preview' => $isPreview,
        ]);
    }
}
