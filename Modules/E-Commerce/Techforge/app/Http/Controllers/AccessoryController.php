<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\StorefrontListing;
use Modules\Ecommerce\Models\StorefrontLayout;

class AccessoryController extends Controller
{
    public function index(Request $request)
    {
        $initialCategory = $request->query('category', 'all');

        $collections = StorefrontListing::where('status', 'active')->latest()->get();

        $collections = $collections->map(function ($item) {
            $item->category = 'Accessory';
            $item->filter_key = 'all';
            $item->rating = 5;
            $item->reviews = rand(50, 300);
            $item->sale = true;
            $item->originalPrice = $item->price * 1.25;
            $item->filter_data = json_encode([]);

            return $item;
        });

        $items = $collections;
        
        $company = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
        $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);
        $pageData = collect($layout['custom_pages'] ?? [])->firstWhere('slug', 'store/accessories') ?? [];

        return view('ecommerce::store.accessories', compact('items', 'initialCategory', 'pageData', 'layout'));
    }

    public function monitors(Request $request)
    {
        $collections = StorefrontListing::where('status', 'active')->latest()->get();

        $collections = $collections->map(function ($item) {
            $item->category = 'Monitor';
            $item->filter_key = 'all';
            $item->rating = 5;
            $item->reviews = rand(50, 300);
            $item->sale = true;
            $item->originalPrice = $item->price * 1.25;
            $item->filter_data = json_encode([]);

            return $item;
        });

        $items = $collections;
        
        $company = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
        $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);
        $pageData = collect($layout['custom_pages'] ?? [])->firstWhere('slug', 'store/monitors') ?? [];

        return view('ecommerce::store.monitors', compact('items', 'pageData', 'layout'));
    }
}

