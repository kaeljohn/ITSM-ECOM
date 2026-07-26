<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use App\Auth\EcommerceAdminUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Manually register the Ecommerce service provider because the
        // module system only scans one level deep in Modules/ directories
        // and the Store module lives at Modules/E-Commerce/Store/.
        $this->app->register(\Modules\Ecommerce\Providers\EcommerceServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('ecommerce-admin-employees', function ($app, array $config) {
            return new EcommerceAdminUserProvider($app['hash'], $config['model']);
        });

        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
