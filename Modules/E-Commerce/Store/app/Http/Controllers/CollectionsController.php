<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;

class CollectionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $laptops = \Modules\Ecommerce\Models\StorefrontListing::where('status', 'active')->latest()->get();
        
        $laptops = $laptops->map(function($laptop) {
            $laptop->setAttribute('html_card', view('ecommerce::components.store-item-card', [
                'id' => $laptop->id,
                'name' => $laptop->name,
                'price' => $laptop->price,
                'image' => $laptop->image_url ?? $laptop->image,
                'category' => $laptop->category,
                'sale' => !empty($laptop->compare_at_price),
                'originalPrice' => $laptop->compare_at_price
            ])->render());
            return $laptop;
        });

        $counts = [
            'brands' => [],
            'categories' => [],
            'tags' => [],
        ];

        foreach ($laptops as $laptop) {
            if (!empty($laptop->category)) {
                $counts['categories'][$laptop->category] = ($counts['categories'][$laptop->category] ?? 0) + 1;
            }
            if (!empty($laptop->brand)) {
                $counts['brands'][$laptop->brand] = ($counts['brands'][$laptop->brand] ?? 0) + 1;
            }
            if (!empty($laptop->tags)) {
                $tags = is_array($laptop->tags) ? $laptop->tags : array_map('trim', explode(',', $laptop->tags));
                foreach ($tags as $t) {
                    if ($t) {
                        $counts['tags'][$t] = ($counts['tags'][$t] ?? 0) + 1;
                    }
                }
            }
        }

        $minPrices = array_filter([\Modules\Ecommerce\Models\StorefrontListing::min('price')]);
        $globalMinPrice = !empty($minPrices) ? floor(min($minPrices)) : 0;
        
        $maxPrices = array_filter([\Modules\Ecommerce\Models\StorefrontListing::max('price')]);
        $globalMaxPrice = !empty($maxPrices) ? ceil(max($maxPrices)) : 5000;

        $company = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
        $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
        $layout = $isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($company) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($company);
        $context = request()->query('context', 'collections');
        $targetSlug = in_array($context, ['collections', 'categories/category1', 'categories/category2', 'categories/category3', 'store/accessories', 'store/monitors', 'store/pc-parts']) ? $context : 'collections';
        $pageData = collect($layout['custom_pages'] ?? [])->firstWhere('slug', $targetSlug) ?? [];
        
        if (!empty($pageData['blocks'])) {
            $ids = array_column($pageData['blocks'], 'listing_id');
            $configs = $laptops->whereIn('id', $ids)->sortBy(function($model) use ($ids) {
                return array_search($model->id, $ids);
            })->values();
            
            if ($isPreview) {
                $dummyConfigs = collect();
                foreach ($pageData['blocks'] as $index => $block) {
                    $listingId = $block['listing_id'] ?? null;
                    $found = $configs->firstWhere('id', $listingId);
                    if ($found) {
                        $dummyConfigs->push($found);
                    } else {
                        $dummy = new \Modules\Ecommerce\Models\StorefrontListing();
                        $dummy->id = $listingId ? (int) $listingId : -(1 + $index);
                        $dummy->name = 'Placeholder Item ' . ($index + 1);
                        $dummy->price = 1000;
                        $dummy->image_url = 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=800&q=80';
                        $dummy->category = 'Accessory';
                        $dummy->setAttribute('html_card', view('ecommerce::components.store-item-card', [
                            'id' => $dummy->id,
                            'name' => $dummy->name,
                            'price' => $dummy->price,
                            'image' => $dummy->image_url,
                            'category' => $dummy->category,
                            'sale' => false,
                            'originalPrice' => null
                        ])->render());
                        $dummyConfigs->push($dummy);
                    }
                }
                $configs = $dummyConfigs;
            }
        } else {
            $configs = $laptops;
            if ($isPreview && $configs->isEmpty()) {
                 $dummy = new \Modules\Ecommerce\Models\StorefrontListing();
                 $dummy->id = -(1);
                 $dummy->name = 'Placeholder Item';
                 $dummy->price = 1000;
                 $dummy->image_url = 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=800&q=80';
                 $dummy->category = 'Accessory';
                 $dummy->setAttribute('html_card', view('ecommerce::components.store-item-card', [
                     'id' => $dummy->id,
                     'name' => $dummy->name,
                     'price' => $dummy->price,
                     'image' => $dummy->image_url,
                     'category' => $dummy->category,
                     'sale' => false,
                     'originalPrice' => null
                 ])->render());
                 $configs = collect([$dummy]);
            }
        }

        $context = request()->query('context', 'collections');
        if (in_array($context, ['categories/category1', 'category1', 'store/accessories', 'accessories'])) {
            $viewName = 'ecommerce::categories.category1';
        } elseif (in_array($context, ['categories/category2', 'category2', 'store/monitors', 'monitors'])) {
            $viewName = 'ecommerce::categories.category2';
        } elseif (in_array($context, ['categories/category3', 'category3', 'store/pc-parts', 'pc-parts'])) {
            $viewName = 'ecommerce::categories.category3';
        } else {
            $viewName = 'ecommerce::collections';
        }

        return view($viewName, compact('laptops', 'configs', 'counts', 'globalMinPrice', 'globalMaxPrice', 'pageData', 'layout'));
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
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
