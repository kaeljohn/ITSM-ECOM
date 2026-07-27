<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\CustomerNotification;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\StorefrontLayout;
use Modules\Ecommerce\Models\StorefrontListing;
use Modules\Ecommerce\Support\EcommerceClientContext;

class EcommerceAdminController extends Controller
{
    public function login() { return view('ecommerce::admin.login'); }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::guard('ecommerce_admin')->attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->route('ecommerce.admin.dashboard');
    }

    public function dashboard()
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        return view('ecommerce::admin.dashboard', [
            'listingCount' => StorefrontListing::count(),
            'activeListingCount' => StorefrontListing::where('status', 'active')->count(),
            'orderCount' => Order::count(),
            'bomCount' => DB::connection('manufacturing')->table('product_boms')->where('client_id', $clientId)->where('status', 'active')->count(),
            'recentListings' => StorefrontListing::latest()->take(5)->get(),
            'recentOrders' => Order::with('items')->latest()->take(5)->get(),
        ]);
    }

    public function listings() { return view('ecommerce::admin.listings', ['listings' => StorefrontListing::latest()->get()]); }
    public function createListing() { return view('ecommerce::admin.listing-form', ['listing' => new StorefrontListing(), 'boms' => $this->boms()]); }

    public function storeListing(Request $request): RedirectResponse
    {
        $data = $this->listingData($request);
        if ($request->hasFile('image')) $data['image_url'] = $request->file('image')->store('storefront-listings', 'public');
        StorefrontListing::create($data);
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing created.');
    }

    public function editListing(StorefrontListing $listing) { return view('ecommerce::admin.listing-form', ['listing' => $listing, 'boms' => $this->boms()]); }

    public function updateListing(Request $request, StorefrontListing $listing): RedirectResponse
    {
        $data = $this->listingData($request);
        if ($request->hasFile('image')) $data['image_url'] = $request->file('image')->store('storefront-listings', 'public');
        $listing->update($data);
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing updated.');
    }

    public function destroyListing(StorefrontListing $listing): RedirectResponse
    {
        $listing->delete();
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing removed.');
    }

    /**
     * GET /ecommerce-admin/crm/api/suggested-listings
     * Return active storefront listings as JSON for the search dropdown.
     */
    public function suggestedListings(): \Illuminate\Http\JsonResponse
    {
        $store = optional($this->company())->ecommerce_slug ?? 'store';

        $listings = StorefrontListing::where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'price' => $l->price,
                'image_url' => $l->image_url ? asset('storage/' . $l->image_url) : null,
                'url' => route('ecommerce.home', ['store' => $store]) . '/listings/' . $l->id,
            ]);

        return response()->json($listings);
    }

    public function orders() { return view('ecommerce::admin.orders', ['orders' => Order::latest()->paginate(20)]); }

    public function updateOrderStatus(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update(['status' => $newStatus]);

        // Notify the customer
        try {
            $statusLabels = [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
            ];

            $company = $this->company();
            $store = $company->ecommerce_slug ?? 'store';

            CustomerNotification::create([
                'client_id' => $company->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
                'title' => 'Order ' . $statusLabels[$newStatus],
                'body' => 'Your order ' . $order->tracking_number . ' has been updated to: ' . $statusLabels[$newStatus] . '.',
                'link' => route('ecommerce.account.orders.show', ['store' => $store, 'id' => $order->id]),
                'icon' => $newStatus === 'delivered' ? 'ph-check-circle' : ($newStatus === 'cancelled' ? 'ph-x-circle' : 'ph-truck'),
                'icon_color' => $newStatus === 'delivered' ? 'green' : ($newStatus === 'cancelled' ? 'red' : 'primary'),
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to create order status notification: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Order status updated to ' . $newStatus]);
    }

    public function editLayout(Request $request)
    {
        $company = $this->company();

        $context = $request->query('context', 'home');

        return view('ecommerce::admin.layout-editor', [
            'layout' => StorefrontLayout::editableFor($company),
            'hasPublishedLayout' => StorefrontLayout::query()->whereNotNull('published_layout')->exists(),
            'company' => $company,
            'availableConfigs' => [],
            'availableListings' => \Modules\Ecommerce\Models\StorefrontListing::orderBy('name')->get(),
            'context' => $context,
        ]);
    }

    public function saveLayout(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $company = $this->company();
        if ($company->id && app(EcommerceClientContext::class)->clientId() === null) {
            app(EcommerceClientContext::class)->setClientId((int) $company->id);
        }
        $record = StorefrontLayout::query()->first() ?: StorefrontLayout::withoutGlobalScope('ecommerce-client')->first();
        $current = $record?->draft_layout ?: $record?->published_layout ?: StorefrontLayout::defaultFor($company);
        $layout = $this->layoutData($request, $current);

        if ($request->hasFile('logo')) {
            $layout['logo_path'] = $request->file('logo')->store('storefront-layouts', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $layout['logo_path'] = null;
        }

        if ($request->hasFile('hero_image')) {
            $layout['sections'] = collect($layout['sections'])->map(function (array $section) use ($request): array {
                if ($section['id'] === 'hero') {
                    $section['image_path'] = $request->file('hero_image')->store('storefront-layouts', 'public');
                }

                return $section;
            })->all();
        }

        // Handle custom_pages hero_image files
        $customPagesFiles = $request->file('custom_pages', []);
        if (is_array($customPagesFiles)) {
            foreach ($customPagesFiles as $index => $pageFiles) {
                if (is_array($pageFiles) && isset($pageFiles['hero_image']) && $pageFiles['hero_image'] instanceof \Illuminate\Http\UploadedFile) {
                    $layout['custom_pages'][$index]['hero_image'] = $pageFiles['hero_image']->store('storefront-layouts', 'public');
                }
            }
        }

        $layoutRecord = StorefrontLayout::withoutGlobalScope('ecommerce-client')->where('client_id', $company->id)->first();
        if ($layoutRecord) {
            $layoutRecord->update(['draft_layout' => $layout]);
        } else {
            StorefrontLayout::create(['client_id' => $company->id, 'draft_layout' => $layout]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Saved successfully']);
        }

        return redirect()->route('ecommerce.admin.layout.edit', ['context' => $request->query('context', 'home')]);
    }

    public function previewLayout(Request $request)
    {
        $company = $this->company();
        if ($company->id && app(EcommerceClientContext::class)->clientId() === null) {
            app(EcommerceClientContext::class)->setClientId((int) $company->id);
        }

        $context = $request->query('context', 'home');

        if ($context && $context !== 'home') {
            $layout = StorefrontLayout::editableFor($company);
            $customPages = collect($layout['custom_pages'] ?? []);
            $page = $customPages->firstWhere('slug', $context);
            $blueprint = $page['blueprint'] ?? $context;

            switch ($blueprint) {
                case 'categories/category1':
                case 'category1':
                case 'store/accessories':
                case 'accessories':
                case 'categories/category2':
                case 'category2':
                case 'store/monitors':
                case 'monitors':
                case 'categories/category3':
                case 'category3':
                case 'store/pc-parts':
                case 'pc-parts':
                case 'collections':
                case 'gaming-laptops':
                    return app(CollectionsController::class)->index();
                case 'prebuilt-pcs':
                    return app(ItemController::class)->index($request);
                case 'cart':
                    return app(CartController::class)->index();
                case 'checkout':
                    return app(CheckoutController::class)->index();
                case 'checkout-success':
                    return view('ecommerce::checkout-success', ['order' => new \Modules\Ecommerce\Models\Order(['id' => 1, 'total_amount' => 0])]);
                case 'account/profile':
                case 'account/purchases':
                    return app(AccountController::class)->index($request);
                case 'account/order-history':
                    return app(AccountController::class)->orderHistory($request);
                case 'search':
                    return app(SearchController::class)->index($request);
                case 'notifications':
                    return view('ecommerce::notifications');
                case 'contact':
                case 'shipping':
                case 'returns':
                case 'about':
                case 'careers':
                case 'affiliates':
                    return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show($blueprint);
            }
        }

        $layout = StorefrontLayout::editableFor($company);
        $hero = collect($layout['sections'] ?? [])->firstWhere('id', 'hero');
        $featuredConfigIds = array_values(array_filter($hero['featured_configs'] ?? []));

        if (!empty($featuredConfigIds)) {
            $customConfigs = StorefrontListing::whereIn('id', $featuredConfigIds)
                ->where('status', 'active')
                ->get()
                ->sortBy(fn($listing) => array_search((string) $listing->id, array_map('strval', $featuredConfigIds)))
                ->values();
        } else {
            $customConfigs = collect([]);
        }

        return view('ecommerce::storefront', [
            'company' => $company,
            'layout' => $layout,
            'storefrontListings' => StorefrontListing::query()->where('status', 'active')->latest()->take(12)->get(),
            'allListings' => StorefrontListing::query()->get()->keyBy('id'),
            'prebuiltPcs' => [],
            'customConfigs' => $customConfigs,
            'preview' => true,
        ]);
    }

    public function publishLayout(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $company = $this->company();
        if ($company->id && app(EcommerceClientContext::class)->clientId() === null) {
            app(EcommerceClientContext::class)->setClientId((int) $company->id);
        }
        $record = StorefrontLayout::query()->first() ?: StorefrontLayout::withoutGlobalScope('ecommerce-client')->first();
        $current = $record?->draft_layout ?: $record?->published_layout ?: StorefrontLayout::defaultFor($company);

        try {
            if ($request->has('brand_name') || $request->has('section_order') || $request->has('hero_title')) {
                $layout = $this->layoutData($request, $current);

                if ($request->hasFile('logo')) {
                    $layout['logo_path'] = $request->file('logo')->store('storefront-layouts', 'public');
                } elseif ($request->boolean('remove_logo')) {
                    $layout['logo_path'] = null;
                }

                if ($request->hasFile('hero_image')) {
                    $layout['sections'] = collect($layout['sections'])->map(function (array $section) use ($request): array {
                        if ($section['id'] === 'hero') {
                            $section['image_path'] = $request->file('hero_image')->store('storefront-layouts', 'public');
                        }

                        return $section;
                    })->all();
                }

                // Handle custom_pages hero_image files
                $customPagesFiles = $request->file('custom_pages', []);
                if (is_array($customPagesFiles)) {
                    foreach ($customPagesFiles as $index => $pageFiles) {
                        if (is_array($pageFiles) && isset($pageFiles['hero_image']) && $pageFiles['hero_image'] instanceof \Illuminate\Http\UploadedFile) {
                            $layout['custom_pages'][$index]['hero_image'] = $pageFiles['hero_image']->store('storefront-layouts', 'public');
                        }
                    }
                }
            } else {
                $layout = $record?->draft_layout ?: $current;
            }

            $layoutRecord = StorefrontLayout::withoutGlobalScope('ecommerce-client')->where('client_id', $company->id)->first();
            if ($layoutRecord) {
                $layoutRecord->update([
                    'draft_layout' => $layout,
                    'published_layout' => $layout,
                ]);
            } else {
                StorefrontLayout::create([
                    'client_id' => $company->id,
                    'draft_layout' => $layout,
                    'published_layout' => $layout,
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Storefront layout published live successfully']);
            }

            return redirect()->route('ecommerce.admin.layout.edit', ['context' => $request->query('context', 'home')])->with('success', 'Your storefront layout is live on '.$company->ecommerce_slug.'.'.config('ecommerce.storefront_base_domain').'.');
        } catch (\Throwable $e) {
            \Log::error('Publish layout error: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->route('ecommerce.admin.layout.edit', ['context' => $request->query('context', 'home')])->withErrors(['layout' => $e->getMessage()]);
        }
    }

    // ============================================================
    // CUSTOMER NOTIFICATIONS MANAGEMENT
    // ============================================================

    /**
     * List all customer-facing notifications.
     */
    public function customerNotifications()
    {
        $company = $this->company();
        $notifications = CustomerNotification::forClient($company->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('ecommerce::admin.customer-notifications', compact('notifications'));
    }

    /**
     * Store a new customer notification (broadcast to all customers).
     */
    public function customerNotificationsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string|max:5000',
            'link' => 'nullable|string|max:500',
            'type' => 'nullable|string|max:60',
            'icon' => 'nullable|string|max:60',
            'icon_color' => 'nullable|string|max:20',
        ]);

        $company = $this->company();

        CustomerNotification::create([
            'client_id' => $company->id,
            'user_id' => null, // broadcast to all
            'type' => $validated['type'] ?? 'general',
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'link' => $validated['link'] ?? null,
            'icon' => $validated['icon'] ?? 'ph-megaphone',
            'icon_color' => $validated['icon_color'] ?? 'blue',
            'is_read' => false,
        ]);

        return redirect()->route('ecommerce.admin.customer-notifications')
            ->with('success', 'Notification sent to all customers.');
    }

    /**
     * Delete a customer notification.
     */
    public function customerNotificationsDelete(int $id): RedirectResponse
    {
        $company = $this->company();
        $notif = CustomerNotification::forClient($company->id)->findOrFail($id);
        $notif->delete();

        return back()->with('success', 'Notification deleted.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('ecommerce_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('ecommerce.admin.login');
    }

    private function boms()
    {
        return DB::connection('manufacturing')->table('product_boms')->where('client_id', app(EcommerceClientContext::class)->clientId())->where('status', 'active')->orderBy('name')->get();
    }

    private function listingData(Request $request): array
    {
        return $request->validate([
            'bom_id' => ['required', 'integer'], 'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'], 'status' => ['required', 'in:draft,active,archived'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function company(): Company
    {
        $admin = auth('ecommerce_admin')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        $company = $admin?->getCompany();

        if (! $company && $clientId > 0) {
            $company = Company::find($clientId);
        }

        if (! $company) {
            $company = Company::first();
        }

        if (! $company) {
            $company = new Company();
            $company->id = $clientId ?: 1;
            $company->company_name = 'E-Commerce Admin';
            $company->ecommerce_slug = 'default';
        }

        return $company;
    }

    private function mergeCustomPages(array $validated, array $current): array
    {
        $currentBySlug = collect($current)->keyBy('slug');
        return collect($validated)->map(function ($page) use ($currentBySlug) {
            $slug = $page['slug'] ?? '';
            $currentData = $currentBySlug->get($slug, []);
            
            // Preserve the hero image if not provided in validated data (which it won't be since it's a file upload)
            if (!isset($page['hero_image']) && isset($currentData['hero_image'])) {
                $page['hero_image'] = $currentData['hero_image'];
            }
            
            return $page;
        })->toArray();
    }

    private function layoutData(Request $request, array $current): array
    {
        $validated = $request->validate([
            'brand_name' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'primary_color' => ['nullable', 'string'],
            'accent_color' => ['nullable', 'string'],
            'section_order' => ['nullable', 'string', 'max:100'],
            'hero_title' => ['nullable', 'string', 'max:160'],
            'hero_title_preset' => ['nullable', 'string', 'in:h1,h2,h3,body'],
            'hero_title_width' => ['nullable', 'string', 'in:auto,full,narrow'],
            'hero_title_color' => ['nullable', 'string'],
            'hero_visual_style' => ['nullable', 'in:showcase,gallery'],
            'hero_badge_text' => ['nullable', 'string', 'max:50'],
            'hero_gallery_cycle' => ['nullable', 'integer', 'min:1', 'max:60'],
            'hero_featured_configs' => ['nullable', 'array', 'max:4'],
            'hero_featured_configs.*' => ['nullable', 'integer'],
            'hero_body' => ['nullable', 'string', 'max:600'],
            'hero_button_alignment' => ['nullable', 'in:start,center,end'],
            'hero_overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'hero_particles_count' => ['nullable', 'integer', 'min:10', 'max:200'],
            'hero_particles_speed' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'hero_cta_subtext' => ['nullable', 'string', 'max:150'],
            'hero_buttons' => ['nullable', 'array', 'max:5'],
            'hero_buttons.*.label' => ['nullable', 'string', 'max:60'],
            'hero_buttons.*.url' => ['nullable', 'string', 'max:255'],
            'hero_buttons.*.style' => ['nullable', 'in:primary,secondary'],
            'hero_stats' => ['nullable', 'array', 'max:3'],
            'hero_stats.*.value' => ['nullable', 'string', 'max:20'],
            'hero_stats.*.label' => ['nullable', 'string', 'max:50'],
            'hero_marquee' => ['nullable', 'array', 'max:10'],
            'hero_marquee.*.text' => ['nullable', 'string', 'max:100'],
            'listings_title' => ['nullable', 'string', 'max:100'],
            'listings_body' => ['nullable', 'string', 'max:300'],
            'tiers_title' => ['nullable', 'string', 'max:140'],
            'tiers_body' => ['nullable', 'string', 'max:600'],
            'tiers_blocks' => ['nullable', 'array', 'max:20'],
            'tiers_blocks.*.listing_id' => ['nullable', 'string', 'max:36'],
            'tiers_blocks.*.description' => ['nullable', 'string', 'max:500'],
            'prebuilts_title' => ['nullable', 'string', 'max:140'],
            'prebuilts_body' => ['nullable', 'string', 'max:600'],
            'prebuilts_blocks' => ['nullable', 'array', 'max:20'],
            'prebuilts_blocks.*.listing_id' => ['nullable', 'string', 'max:36'],
            'prebuilts_blocks.*.description' => ['nullable', 'string', 'max:500'],
            'categories_title' => ['nullable', 'string', 'max:140'],
            'categories_body' => ['nullable', 'string', 'max:600'],
            'cta_title' => ['nullable', 'string', 'max:140'],
            'cta_subtitle' => ['nullable', 'string', 'max:140'],
            'cta_body' => ['nullable', 'string', 'max:600'],
            'cta_primary_button_label' => ['nullable', 'string', 'max:60'],
            'cta_primary_button_url' => ['nullable', 'string', 'max:255'],
            'cta_secondary_button_label' => ['nullable', 'string', 'max:60'],
            'cta_secondary_button_url' => ['nullable', 'string', 'max:255'],
            'cta_tag_text' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'announcement_text' => ['nullable', 'string', 'max:100'],
            'announcement_url' => ['nullable', 'string', 'max:255'],
            'search_placeholder' => ['nullable', 'string', 'max:50'],
            'trending_searches' => ['nullable', 'string', 'max:255'],
                        'nav_links' => ['nullable', 'array', 'max:10'],
            'nav_links.*.label' => ['nullable', 'string', 'max:50'],
            'nav_links.*.url' => ['nullable', 'string', 'max:255'],
            'nav_links.*.type' => ['nullable', 'in:simple,mega'],
            'nav_links.*.promo_title' => ['nullable', 'string', 'max:100'],
            'nav_links.*.promo_subtitle' => ['nullable', 'string', 'max:200'],
            'nav_links.*.promo_button' => ['nullable', 'string', 'max:60'],
            'nav_links.*.promo_button_url' => ['nullable', 'string', 'max:255'],
            'custom_pages' => ['nullable', 'array', 'max:50'],
            'custom_pages.*.id' => ['required_with:custom_pages', 'string'],
            'custom_pages.*.title' => ['required_with:custom_pages', 'string', 'max:160'],
            'custom_pages.*.slug' => ['required_with:custom_pages', 'string', 'max:100'],
            'custom_pages.*.blueprint' => ['required_with:custom_pages', 'string', 'max:50'],
            'custom_pages.*.subtitle' => ['nullable', 'string', 'max:500'],
            'custom_pages.*.show_hero' => ['nullable', 'boolean'],
            'custom_pages.*.category_buttons' => ['nullable', 'array', 'max:5'],
            'custom_pages.*.category_buttons.*.label' => ['nullable', 'string', 'max:60'],
            'custom_pages.*.category_buttons.*.url' => ['nullable', 'string', 'max:255'],
            'custom_pages.*.blocks' => ['nullable', 'array'],
            'custom_pages.*.blocks.*.listing_id' => ['nullable', 'string'],
            'custom_pages.*.hero_image' => ['nullable', 'image', 'max:4096'],
            'footer_description' => ['nullable', 'string', 'max:500'],
            'footer_copyright_text' => ['nullable', 'string', 'max:255'],
            'footer_social_instagram' => ['nullable', 'string', 'max:255'],
            'footer_social_twitter' => ['nullable', 'string', 'max:255'],
            'footer_social_facebook' => ['nullable', 'string', 'max:255'],
            'footer_social_youtube' => ['nullable', 'string', 'max:255'],
            'footer_column_1_title' => ['nullable', 'string', 'max:50'],
            'footer_column_2_title' => ['nullable', 'string', 'max:50'],
            'footer_column_3_title' => ['nullable', 'string', 'max:50'],
            'footer_shop_links' => ['nullable', 'array', 'max:10'],
            'footer_shop_links.*.label' => ['nullable', 'string', 'max:60'],
            'support_pages' => ['nullable', 'array'],
            'support_pages.contact' => ['nullable', 'array'],
            'support_pages.contact.title' => ['nullable', 'string', 'max:160'],
            'support_pages.contact.subtitle' => ['nullable', 'string', 'max:300'],
            'support_pages.contact.cards' => ['nullable', 'array', 'max:10'],
            'support_pages.contact.cards.*.icon' => ['nullable', 'string', 'max:30'],
            'support_pages.contact.cards.*.title' => ['nullable', 'string', 'max:60'],
            'support_pages.contact.cards.*.detail' => ['nullable', 'string', 'max:100'],
            'support_pages.contact.cards.*.sub' => ['nullable', 'string', 'max:100'],
            'support_pages.contact.faq_title' => ['nullable', 'string', 'max:160'],
            'support_pages.contact.faq_items' => ['nullable', 'array', 'max:30'],
            'support_pages.contact.faq_items.*.q' => ['nullable', 'string', 'max:300'],
            'support_pages.contact.faq_items.*.a' => ['nullable', 'string', 'max:800'],
            'support_pages.shipping' => ['nullable', 'array'],
            'support_pages.shipping.title' => ['nullable', 'string', 'max:160'],
            'support_pages.shipping.subtitle' => ['nullable', 'string', 'max:300'],
            'support_pages.shipping.rates' => ['nullable', 'array', 'max:10'],
            'support_pages.shipping.rates.*.label' => ['nullable', 'string', 'max:80'],
            'support_pages.shipping.rates.*.desc' => ['nullable', 'string', 'max:150'],
            'support_pages.shipping.rates.*.price' => ['nullable', 'string', 'max:20'],
            'support_pages.shipping.rates.*.highlighted' => ['nullable', 'boolean'],
            'support_pages.shipping.processing' => ['nullable', 'array', 'max:10'],
            'support_pages.shipping.processing.*.label' => ['nullable', 'string', 'max:60'],
            'support_pages.shipping.processing.*.desc' => ['nullable', 'string', 'max:150'],
            'support_pages.shipping.tracking_body' => ['nullable', 'string', 'max:500'],
            'support_pages.returns' => ['nullable', 'array'],
            'support_pages.returns.title' => ['nullable', 'string', 'max:160'],
            'support_pages.returns.subtitle' => ['nullable', 'string', 'max:300'],
            'support_pages.returns.warranty_title' => ['nullable', 'string', 'max:160'],
            'support_pages.returns.warranty_sub' => ['nullable', 'string', 'max:200'],
            'support_pages.returns.warranty_body' => ['nullable', 'string', 'max:800'],
            'support_pages.returns.policy' => ['nullable', 'array', 'max:10'],
            'support_pages.returns.policy.*.icon' => ['nullable', 'string', 'max:20'],
            'support_pages.returns.policy.*.color' => ['nullable', 'string', 'max:10'],
            'support_pages.returns.policy.*.title' => ['nullable', 'string', 'max:80'],
            'support_pages.returns.policy.*.desc' => ['nullable', 'string', 'max:300'],
            'support_pages.returns.process_title' => ['nullable', 'string', 'max:160'],
            'support_pages.returns.process_sub' => ['nullable', 'string', 'max:200'],
            'support_pages.returns.steps' => ['nullable', 'array', 'max:10'],
            'support_pages.returns.steps.*.num' => ['nullable', 'integer'],
            'support_pages.returns.steps.*.title' => ['nullable', 'string', 'max:80'],
            'support_pages.returns.steps.*.desc' => ['nullable', 'string', 'max:300'],
            'company_pages' => ['nullable', 'array'],
            'company_pages.about' => ['nullable', 'array'],
            'company_pages.about.title' => ['nullable', 'string', 'max:160'],
            'company_pages.about.subtitle' => ['nullable', 'string', 'max:300'],
            'company_pages.about.story' => ['nullable', 'string', 'max:1000'],
            'company_pages.about.values' => ['nullable', 'array', 'max:10'],
            'company_pages.about.values.*.icon' => ['nullable', 'string', 'max:30'],
            'company_pages.about.values.*.title' => ['nullable', 'string', 'max:60'],
            'company_pages.about.values.*.description' => ['nullable', 'string', 'max:200'],
            'company_pages.about.cta_label' => ['nullable', 'string', 'max:60'],
            'company_pages.careers' => ['nullable', 'array'],
            'company_pages.careers.title' => ['nullable', 'string', 'max:160'],
            'company_pages.careers.subtitle' => ['nullable', 'string', 'max:300'],
            'company_pages.careers.body' => ['nullable', 'string', 'max:1000'],
            'company_pages.careers.open_positions' => ['nullable', 'array', 'max:30'],
            'company_pages.careers.open_positions.*.title' => ['nullable', 'string', 'max:100'],
            'company_pages.careers.open_positions.*.location' => ['nullable', 'string', 'max:100'],
            'company_pages.careers.open_positions.*.type' => ['nullable', 'string', 'max:50'],
            'company_pages.affiliates' => ['nullable', 'array'],
            'company_pages.affiliates.title' => ['nullable', 'string', 'max:160'],
            'company_pages.affiliates.subtitle' => ['nullable', 'string', 'max:300'],
            'company_pages.affiliates.body' => ['nullable', 'string', 'max:1000'],
            'company_pages.affiliates.benefits' => ['nullable', 'array', 'max:10'],
            'company_pages.affiliates.benefits.*.icon' => ['nullable', 'string', 'max:30'],
            'company_pages.affiliates.benefits.*.title' => ['nullable', 'string', 'max:60'],
            'company_pages.affiliates.benefits.*.description' => ['nullable', 'string', 'max:200'],
            'company_pages.affiliates.cta_label' => ['nullable', 'string', 'max:60'],
        ]);

        $context = $request->query('context', 'home');
        $allowedSections = ['hero', 'tiers', 'prebuilts', 'categories', 'cta'];

        if ($context === 'home') {
            $order = array_values(array_unique(array_filter(explode(',', $validated['section_order'] ?? ''), fn (string $id): bool => in_array($id, $allowedSections, true))));
            $order = array_values(array_unique([...$order, ...$allowedSections]));
            $existing = collect($current['sections'] ?? [])->keyBy('id');
            $section = fn (string $id): array => (array) $existing->get($id, ['id' => $id]);

            $hero = $section('hero');
            $heroButtons = collect($validated['hero_buttons'] ?? [])->map(fn($btn) => [
                'label' => $btn['label'],
                'url' => $this->safeStorefrontLink($btn['url'] ?? '#products'),
                'style' => $btn['style'] ?? 'primary'
            ])->all();
            $hero = [
                ...$hero,
                'id' => 'hero',
                'enabled' => $request->boolean('hero_enabled'),
                'title' => $validated['hero_title'] ?: 'Products built for your {next big move}.',
                'title_preset' => $validated['hero_title_preset'] ?? 'h1',
                'title_width' => $validated['hero_title_width'] ?? 'auto',
                'title_color' => $validated['hero_title_color'] ?? '#ffffff',
                'body' => $validated['hero_body'] ?: '',
                'button_alignment' => $validated['hero_button_alignment'] ?? 'start',
                'buttons' => $heroButtons,
                'cta_subtext' => $validated['hero_cta_subtext'] ?? '',
                'visual_style' => $validated['hero_visual_style'] ?? 'showcase',
                'badge_text' => $validated['hero_badge_text'] ?? 'FEATURED BUILD',
                'gallery_cycle' => $validated['hero_gallery_cycle'] ?? 5,
                'featured_configs' => array_values(array_filter($validated['hero_featured_configs'] ?? [])),
                'overlay_opacity' => $validated['hero_overlay_opacity'] ?? 0,
                'hero_stats' => $validated['hero_stats'] ?? [],
                'hero_marquee' => $validated['hero_marquee'] ?? [],
                'particles_enabled' => $request->boolean('hero_particles_enabled'),
                'particles_count' => $validated['hero_particles_count'] ?? 40,
                'particles_speed' => $validated['hero_particles_speed'] ?? 1.0
            ];

            $tiers = $section('tiers');
            $tiers = [...$tiers, 'id' => 'tiers', 'enabled' => $request->boolean('tiers_enabled'), 'title' => $validated['tiers_title'] ?: "Select\nYour Tier", 'body' => $validated['tiers_body'] ?: 'Four configurations. Every one tested under load for 72 hours before it leaves our facility.', 'blocks' => array_values($validated['tiers_blocks'] ?? [])];

            $prebuilts = $section('prebuilts');
            $prebuilts = [...$prebuilts, 'id' => 'prebuilts', 'enabled' => $request->boolean('prebuilts_enabled'), 'title' => $validated['prebuilts_title'] ?: "Pre-Built\nSystems", 'body' => $validated['prebuilts_body'] ?: 'Ready to ship. Professionally assembled and stress-tested for out-of-the-box performance.', 'blocks' => array_values($validated['prebuilts_blocks'] ?? [])];

            $categories = $section('categories');
            $categories = [...$categories, 'id' => 'categories', 'enabled' => $request->boolean('categories_enabled'), 'title' => $validated['categories_title'] ?: "Explore\nCategories", 'body' => $validated['categories_body'] ?: 'Find exactly what you need. From ready-to-ship systems to fully custom workstations.'];

            $cta = $section('cta');
            $cta = [...$cta, 'id' => 'cta', 'enabled' => $request->boolean('cta_enabled'), 'title' => $validated['cta_title'] ?: "Stop Settling.", 'subtitle' => $validated['cta_subtitle'] ?: "Start Winning.", 'body' => $validated['cta_body'] ?: 'Free shipping. Free setup support. 30-day no-questions return policy. Your next machine is three clicks away.', 'primary_button_label' => $validated['cta_primary_button_label'] ?: 'Build Yours Now', 'primary_button_url' => $this->safeStorefrontLink($validated['cta_primary_button_url'] ?? '/configurator'), 'secondary_button_label' => $validated['cta_secondary_button_label'] ?: 'Talk To An Expert', 'secondary_button_url' => $this->safeStorefrontLink($validated['cta_secondary_button_url'] ?? '/contact'), 'tag_text' => $validated['cta_tag_text'] ?: 'READY_TO_BUILD'];

            $sectionsById = [
                'hero' => $hero,
                'tiers' => $tiers,
                'prebuilts' => $prebuilts,
                'categories' => $categories,
                'cta' => $cta,
            ];

            $sections = array_map(fn (string $id): array => $sectionsById[$id], $order);
        } else {
            $sections = $current['sections'] ?? [];
        }

        return [
            'brand_name' => $validated['brand_name'] ?? $current['brand_name'] ?? 'Nexora',
            'tagline' => $validated['tagline'] ?: 'Official Nexora storefront',
            'primary_color' => $validated['primary_color'] ?? $current['primary_color'] ?? '#ff6b00',
            'accent_color' => $validated['accent_color'] ?? $current['accent_color'] ?? '#f59e0b',
            'logo_path' => $current['logo_path'] ?? null,
            'custom_pages' => $this->mergeCustomPages($validated['custom_pages'] ?? [], $current['custom_pages'] ?? []),
            'support_pages' => $validated['support_pages'] ?? $current['support_pages'] ?? [],
            'company_pages' => $validated['company_pages'] ?? $current['company_pages'] ?? [],
            'sections' => $sections,
            'navbar' => [
                'announcement_enabled' => $request->has('announcement_enabled') ? $request->boolean('announcement_enabled') : ($current['navbar']['announcement_enabled'] ?? true),
                'announcement_text' => $validated['announcement_text'] ?? $current['navbar']['announcement_text'] ?? '🔥 Free shipping on all orders over ₱50,000!',
                'announcement_url' => $validated['announcement_url'] ?? $current['navbar']['announcement_url'] ?? '',
                'search_placeholder' => $validated['search_placeholder'] ?? $current['navbar']['search_placeholder'] ?? 'What are we searching?',
                'trending_searches' => $validated['trending_searches'] ?? $current['navbar']['trending_searches'] ?? 'RTX 4090, Ryzen 7 7800X3D, Prebuilt Gaming PC, 32GB DDR5 RAM',
                'links' => $validated['nav_links'] ?? $current['navbar']['links'] ?? [],
            ],
            'footer' => [
                'description' => $validated['footer_description'] ?? $current['footer']['description'] ?? 'Performance-driven computers and accessories for every digital journey.',
                'copyright_text' => $validated['footer_copyright_text'] ?? $current['footer']['copyright_text'] ?? 'All rights reserved.',
                'social_instagram' => $validated['footer_social_instagram'] ?? $current['footer']['social_instagram'] ?? '#',
                'social_twitter' => $validated['footer_social_twitter'] ?? $current['footer']['social_twitter'] ?? '#',
                'social_facebook' => $validated['footer_social_facebook'] ?? $current['footer']['social_facebook'] ?? '#',
                'social_youtube' => $validated['footer_social_youtube'] ?? $current['footer']['social_youtube'] ?? '#',
                'column_1_title' => $validated['footer_column_1_title'] ?? $current['footer']['column_1_title'] ?? 'Shop',
                'column_2_title' => $validated['footer_column_2_title'] ?? $current['footer']['column_2_title'] ?? 'Support',
                'column_3_title' => $validated['footer_column_3_title'] ?? $current['footer']['column_3_title'] ?? 'Company',
                'shop_links' => array_values(array_filter($validated['footer_shop_links'] ?? $current['footer']['shop_links'] ?? [
                    ['label' => 'Collections', 'url' => '/collections'],
                    ['label' => 'Category 1', 'url' => '/categories/category1'],
                    ['label' => 'Category 2', 'url' => '/categories/category2'],
                    ['label' => 'Category 3', 'url' => '/categories/category3'],
                ], fn($l) => !empty(trim($l['label'] ?? '')))),
                'company_links' => [
                    ['label' => 'About Us', 'url' => '/about'],
                    ['label' => 'Careers', 'url' => '/careers'],
                    ['label' => 'Affiliates', 'url' => '/affiliates'],
                ],
            ],
        ];
    }

    private function safeStorefrontLink(?string $url): string
    {
        $url = trim((string) $url);

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL)
            ? $url
            : '#products';
    }
}
