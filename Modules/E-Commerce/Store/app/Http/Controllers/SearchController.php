<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\StorefrontListing;
use Modules\Ecommerce\Models\StorefrontLayout;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->query('q', '');
        $sort = $request->query('sort', 'Recommended');

        // Resolve company/store context
        $company = $request->attributes->get('ecommerce_company') ?? \App\Models\Company::first();
        $store = $company?->ecommerce_slug ?? 'store';

        // Resolve layout for theme colors and branding
        $isPreview = $request->boolean('preview') && auth('ecommerce_admin')->check();
        $layout = $isPreview ? StorefrontLayout::editableFor($company) : StorefrontLayout::publishedFor($company);
        $brandName = $layout['brand_name'] ?? ($company?->company_name ?: 'Nexora Store');
        $logoUrl = !empty($layout['logo_path'])
            ? (str_starts_with($layout['logo_path'], 'Modules/') ? Vite::asset($layout['logo_path']) : asset('storage/'.$layout['logo_path']))
            : ($company?->logoUrl() ?: asset('ecommerce/Nexora_Logo.png'));

        // Theme color calculation from layout
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

        // Build the listing query — client-scoped via BelongsToClient trait
        $listingsQuery = StorefrontListing::where('status', 'active');

        if ($query) {
            $listingsQuery->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', '%' . $query . '%')
                  ->orWhere('description', 'ILIKE', '%' . $query . '%')
                  ->orWhere('sku', 'ILIKE', '%' . $query . '%');
            });
        }

        // Sorting
        if ($sort === 'Price: Low to High') {
            $listingsQuery->orderBy('price', 'asc');
        } elseif ($sort === 'Price: High to Low') {
            $listingsQuery->orderBy('price', 'desc');
        } else {
            $listingsQuery->orderBy('created_at', 'desc');
        }

        $listings = $listingsQuery->get();

        // Price range for filter
        $globalMinPrice = (float) StorefrontListing::where('status', 'active')->min('price') ?: 0;
        $globalMaxPrice = (float) StorefrontListing::where('status', 'active')->max('price') ?: 250000;

        // Use tab as a simple category filter (based on categories or just 'all')
        $tab = $request->query('tab', 'all');

        $totalResults = $listings->count();

        return view('ecommerce::search', compact(
            'query', 'tab', 'listings', 'totalResults',
            'globalMinPrice', 'globalMaxPrice',
            'company', 'store', 'layout', 'brandName', 'logoUrl',
            'primaryHex', 'primaryR', 'primaryG', 'primaryB',
            'accentHex', 'accentR', 'accentG', 'accentB'
        ));
    }

    public function suggestions(Request $request)
    {
        $query = $request->query('q', '');
        if (empty($query)) {
            return response()->json([]);
        }

        $results = StorefrontListing::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', '%' . $query . '%')
                  ->orWhere('sku', 'ILIKE', '%' . $query . '%');
            })
            ->limit(6)
            ->get(['name', 'price', 'sku', 'image_url']);

        $formatted = $results->map(function ($item) {
            return [
                'name' => $item->name,
                'type' => $item->sku ? 'SKU: ' . $item->sku : 'Product',
                'price' => '₱' . number_format((float) $item->price, 2),
                'image_url' => $item->image_url,
            ];
        });

        return response()->json($formatted);
    }
}
