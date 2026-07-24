<?php

namespace Modules\Ecommerce\Http\Controllers;

use Modules\Ecommerce\Models\Laptop;
use Illuminate\Http\Request;

class LaptopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $laptops = \Modules\Ecommerce\Models\StorefrontListing::where('status', 'active')->latest()->get();
        
        $laptops = $laptops->map(function($laptop) {
            $laptop->html_card = view('ecommerce::components.store-item-card', ['item' => $laptop])->render();
            return $laptop;
        });

        $counts = [
            'brands' => [],
        ];

        foreach ($laptops as $laptop) {
            // empty for generic pages
        }

        $minPrices = array_filter([\Modules\Ecommerce\Models\StorefrontListing::min('price')]);
        $globalMinPrice = !empty($minPrices) ? floor(min($minPrices)) : 0;
        
        $maxPrices = array_filter([\Modules\Ecommerce\Models\StorefrontListing::max('price')]);
        $globalMaxPrice = !empty($maxPrices) ? ceil(max($maxPrices)) : 5000;

        $company = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
        $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
        $layout = $isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($company) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($company);
        $pageData = collect($layout['custom_pages'] ?? [])->firstWhere('slug', 'gaming-laptops') ?? [];

        return view('ecommerce::gaming-laptops', compact('laptops', 'counts', 'globalMinPrice', 'globalMaxPrice', 'pageData', 'layout'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Laptop $laptop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Laptop $laptop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Laptop $laptop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Laptop $laptop)
    {
        //
    }
}
