<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Storefront route group - shared between subdomain and localhost
$storefrontRoutes = function () {
    Route::get('/debug-session', function () {
        return [
            'session_id' => session()->getId(),
            'session_all' => session()->all(),
            'cookies' => request()->cookies->all(),
            'auth_check' => \Illuminate\Support\Facades\Auth::guard('ecommerce')->check(),
            'auth_id' => \Illuminate\Support\Facades\Auth::guard('ecommerce')->id(),
            'client_context' => app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId(),
        ];
    });

    Route::get('/', [\Modules\Ecommerce\Http\Controllers\StorefrontController::class, 'index'])->name('home');

    Route::get('/login', function () {
        return view('ecommerce::auth.login');
    })->name('login');

    Route::get('/register', function () {
        return redirect()->route('ecommerce.login', ['register' => 'true']);
    })->name('register');

    Route::post('/login', [\Modules\Ecommerce\Http\Controllers\AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [\Modules\Ecommerce\Http\Controllers\AuthController::class, 'register'])->name('register.post');
    Route::post('/logout', [\Modules\Ecommerce\Http\Controllers\AuthController::class, 'logout'])->name('logout');
    Route::get('/listings/{listing}', [\Modules\Ecommerce\Http\Controllers\StorefrontListingController::class, 'show'])->name('listings.show');
    Route::post('/listings/{listing}/cart', [\Modules\Ecommerce\Http\Controllers\StorefrontListingController::class, 'addToCart'])->name('listings.cart');

    // Social Auth Routes
    Route::get('/auth/complete-registration', [\Modules\Ecommerce\Http\Controllers\Auth\SocialAuthController::class, 'completeRegistration'])->name('social.complete-registration');
    Route::post('/auth/complete-registration', [\Modules\Ecommerce\Http\Controllers\Auth\SocialAuthController::class, 'processRegistration'])->name('social.process-registration');
    Route::get('/auth/{provider}', [\Modules\Ecommerce\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\Modules\Ecommerce\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('social.callback');

    Route::get('/cart', [\Modules\Ecommerce\Http\Controllers\CartController::class, 'index'])->name('cart');
    Route::post('/cart/add', [\Modules\Ecommerce\Http\Controllers\CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update-quantity', [\Modules\Ecommerce\Http\Controllers\CartController::class, 'updateQuantity'])->name('cart.update-quantity');
    Route::delete('/cart/remove', [\Modules\Ecommerce\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
    Route::get('/cart/count', [\Modules\Ecommerce\Http\Controllers\CartController::class, 'getCount'])->name('cart.count');

    Route::get('/cart/checkout-redirect', function () {
        if (\Illuminate\Support\Facades\Auth::guard('ecommerce')->check()) {
            return redirect()->route('ecommerce.checkout.index');
        }
        session()->put('redirect_after_auth', route('ecommerce.cart'));
        return redirect()->route('ecommerce.login');
    })->name('cart.checkout.redirect');

    Route::get('/notifications', function () {
        return view('ecommerce::notifications');
    })->name('notifications');

    Route::middleware([\Modules\Ecommerce\Http\Middleware\RequireEcommerceAuth::class])->group(function () {
        Route::get('/account/profile', [\Modules\Ecommerce\Http\Controllers\AccountController::class, 'index'])->name('account.profile');
        Route::get('/account/purchases', [\Modules\Ecommerce\Http\Controllers\AccountController::class, 'index'])->name('account.purchases');
        Route::get('/account/order-history', [\Modules\Ecommerce\Http\Controllers\AccountController::class, 'orderHistory'])->name('account.order-history');
        Route::get('/account/orders/{id}', [\Modules\Ecommerce\Http\Controllers\AccountController::class, 'showOrder'])->name('account.orders.show');
        Route::post('/account/profile', [\Modules\Ecommerce\Http\Controllers\AccountController::class, 'updateProfile'])->name('account.profile.update');

        Route::post('/account/payment-methods/card', [\Modules\Ecommerce\Http\Controllers\PaymentMethodController::class, 'storeCard'])->name('account.payment-methods.store-card');
        Route::post('/account/payment-methods/bank', [\Modules\Ecommerce\Http\Controllers\PaymentMethodController::class, 'storeBank'])->name('account.payment-methods.store-bank');
        Route::delete('/account/payment-methods/{paymentMethod}', [\Modules\Ecommerce\Http\Controllers\PaymentMethodController::class, 'destroy'])->name('account.payment-methods.destroy');
        Route::put('/account/payment-methods/{paymentMethod}', [\Modules\Ecommerce\Http\Controllers\PaymentMethodController::class, 'update'])->name('account.payment-methods.update');
        Route::post('/account/payment-methods/{paymentMethod}/default', [\Modules\Ecommerce\Http\Controllers\PaymentMethodController::class, 'setDefault'])->name('account.payment-methods.set-default');

        Route::post('/account/addresses', [\Modules\Ecommerce\Http\Controllers\AddressController::class, 'store'])->name('account.addresses.store');
        Route::put('/account/addresses/{address}', [\Modules\Ecommerce\Http\Controllers\AddressController::class, 'update'])->name('account.addresses.update');
        Route::delete('/account/addresses/{address}', [\Modules\Ecommerce\Http\Controllers\AddressController::class, 'destroy'])->name('account.addresses.destroy');
        Route::post('/account/addresses/{address}/default', [\Modules\Ecommerce\Http\Controllers\AddressController::class, 'setDefault'])->name('account.addresses.set-default');
    });

    Route::get('/checkout', [\Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [\Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success/{id}', [\Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');



    Route::get('/collections', [\Modules\Ecommerce\Http\Controllers\CollectionsController::class, 'index'])->name('collections');
    Route::get('/categories/category1', function () {
        return view('ecommerce::categories.category1');
    })->name('categories.category1');
    Route::get('/categories/category2', function () {
        return view('ecommerce::categories.category2');
    })->name('categories.category2');
    Route::get('/categories/category3', function () {
        return view('ecommerce::categories.category3');
    })->name('categories.category3');

    Route::get('/store/accessories', function() {
        return redirect()->route('ecommerce.categories.category1');
    })->name('store.accessories');
    Route::get('/store/monitors', function() {
        return redirect()->route('ecommerce.categories.category2');
    })->name('store.monitors');
    Route::get('/search', [\Modules\Ecommerce\Http\Controllers\SearchController::class, 'index'])->name('search');
    Route::get('/api/search/suggestions', [\Modules\Ecommerce\Http\Controllers\SearchController::class, 'suggestions'])->name('search.suggestions');

    Route::get('/prebuilt-pcs', [\Modules\Ecommerce\Http\Controllers\ItemController::class, 'index'])->name('prebuilt-pcs');

    // Support / Info Pages — using closures to avoid defaults() parameter resolution quirks
    Route::get('/contact', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('contact'); })->name('pages.contact');
    Route::get('/shipping', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('shipping'); })->name('pages.shipping');
    Route::get('/returns', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('returns'); })->name('pages.returns');
    Route::get('/about', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('about'); })->name('pages.about');
    Route::get('/careers', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('careers'); })->name('pages.careers');
    Route::get('/affiliates', function () { return app(\Modules\Ecommerce\Http\Controllers\PageController::class)->show('affiliates'); })->name('pages.affiliates');

    // Exclude static pages — they have explicit routes defined above.
    // If you add a new page route above, add its slug here too.
    Route::get('/{slug}', [\Modules\Ecommerce\Http\Controllers\DynamicPageController::class, 'show'])
        ->where('slug', '(?!contact$|shipping$|returns$|about$|careers$|affiliates$).*')
        ->name('dynamic.page');
};

// ============================================================
// PRODUCTION: Subdomain-based routing ({store}.shop.section4.tech)
// ============================================================
Route::domain('{store}.'.config('ecommerce.storefront_base_domain'))
    ->middleware('ecommerce.client')
    ->name('ecommerce.')
    ->group($storefrontRoutes);

// ============================================================
// LOCALHOST FALLBACK: Access storefront without subdomain
// Only active when ECOMMERCE_LOCALHOST_FALLBACK=true in .env
// ============================================================
if (config('ecommerce.localhost_fallback', false)) {
    Route::middleware('web')
        ->group(function () {
            // Resolve the default company as the store for local testing
            Route::middleware(\Modules\Ecommerce\Http\Middleware\ResolveDefaultClientForLocalhost::class)
                ->name('ecommerce.')
                ->group($storefrontRoutes);
        });
}

// ============================================================
// E-COMMERCE ADMIN ROUTES (no subdomain required)
// ============================================================
Route::name('ecommerce.')->group(function () {
    Route::prefix('ecommerce-admin')->name('admin.')->group(function (): void {
        Route::get('/login', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'login'])->name('login');
        Route::post('/login', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'authenticate'])->name('login.post');

        Route::middleware('ecommerce.admin')->group(function (): void {
            Route::get('/', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/listings', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'listings'])->name('listings');
            Route::get('/listings/create', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'createListing'])->name('listings.create');
            Route::post('/listings', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'storeListing'])->name('listings.store');
            Route::get('/listings/{listing}/edit', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'editListing'])->name('listings.edit');
            Route::put('/listings/{listing}', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'updateListing'])->name('listings.update');
            Route::delete('/listings/{listing}', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'destroyListing'])->name('listings.destroy');
            Route::get('/orders', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'orders'])->name('orders');
            Route::get('/layout', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'editLayout'])->name('layout.edit');
            Route::put('/layout', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'saveLayout'])->name('layout.save');
            Route::get('/layout/preview', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'previewLayout'])->name('layout.preview');
            Route::post('/layout/publish', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'publishLayout'])->name('layout.publish');
            Route::post('/logout', [\Modules\Ecommerce\Http\Controllers\EcommerceAdminController::class, 'logout'])->name('logout');
        });
    });
});

// Local dev callback override for social auth
if (app()->environment('local')) {
    Route::get('/auth/{provider}/callback', function($provider, Request $request) {
        $domain = config('ecommerce.storefront_base_domain');
        if ($request->getHost() !== $domain) {
            $port = request()->getPort();
            $portStr = ($port != 80 && $port != 443) ? (':' . $port) : '';
            $url = 'http://' . $domain . $portStr . $request->getRequestUri();
            return redirect($url);
        }

        return app(\Modules\Ecommerce\Http\Controllers\Auth\SocialAuthController::class)->callback($provider);
    });
}
