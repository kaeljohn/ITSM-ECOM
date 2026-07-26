<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\StorefrontListing;
use Modules\Ecommerce\Models\StorefrontLayout;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $allConfigs = StorefrontListing::where('status', 'active')->latest()->get();
        
        $counts = [
            'brands' => [],
        ];

        foreach ($allConfigs as $config) {
            // we can extract counts if they had tags or categories, for now just empty counts
        }

        $configs = $allConfigs->map(function($config) {
            $config->setAttribute('html_card', view('ecommerce::components.store-item-card', [
                'id' => 'listing-' . $config->id,
                'name' => $config->name,
                'price' => $config->price,
                'image' => $config->image_url ?? $config->image,
                'category' => $config->category,
                'sale' => !empty($config->compare_at_price),
                'originalPrice' => $config->compare_at_price,
                'productType' => $config->bom_id ? 'bom_listing' : 'generic',
                'configuration' => $config->bom_id ? json_encode(['bom_id' => (int) $config->bom_id, 'listing_id' => $config->id]) : null
            ])->render());
            return $config;
        });

        $minPrices = array_filter([
            StorefrontListing::min('price'),
        ]);
        $globalMinPrice = !empty($minPrices) ? floor(min($minPrices)) : 0;

        $maxPrices = array_filter([
            StorefrontListing::max('price'),
        ]);
        $globalMaxPrice = !empty($maxPrices) ? ceil(max($maxPrices)) : 250000;

        $company = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
        $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);
        $pageData = collect($layout['custom_pages'] ?? [])->firstWhere('slug', 'prebuilt-pcs') ?? [];

        return view('ecommerce::prebuilt-pcs', compact('configs', 'counts', 'globalMinPrice', 'globalMaxPrice', 'pageData', 'layout'));
    }
}
