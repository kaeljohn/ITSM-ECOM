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

        $layout = StorefrontLayout::publishedFor($company);
        
        $hero = collect($layout['sections'] ?? [])->firstWhere('id', 'hero');
        $featuredConfigIds = $hero['featured_configs'] ?? [];
        
        if (!empty($featuredConfigIds)) {
            $configs = CustombuiltConfig::withoutGlobalScope('ecommerce-client')
                             ->whereIn('id', $featuredConfigIds)
                             ->get();
                             
            // Reorder the fetched models to precisely match the array of chosen IDs
            $customConfigs = collect($featuredConfigIds)
                ->map(fn($id) => $configs->firstWhere('id', $id))
                ->filter()
                ->values();
        } else {
            $customConfigs = CustombuiltConfig::withoutGlobalScope('ecommerce-client')->latest()->take(4)->get();
        }

        return view('ecommerce::storefront', [
            'company' => $company,
            'layout' => $layout,
            'storefrontListings' => StorefrontListing::query()->where('status', 'active')->latest()->take(12)->get(),
            'prebuiltPcs' => PrebuiltConfig::query()->latest()->take(6)->get(),
            'customConfigs' => $customConfigs,
            'preview' => false,
        ]);
    }
}
