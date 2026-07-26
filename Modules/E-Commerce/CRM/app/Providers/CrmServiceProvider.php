<?php

namespace Modules\Ecommerce\CRM\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\CRM\Console\Commands\SyncCrmCustomers;
use Modules\Ecommerce\CRM\Console\Commands\FlagAbandonedCarts;
use Modules\Ecommerce\CRM\Services\CrmDashboardService;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CrmDashboardService::class, fn (): CrmDashboardService => new CrmDashboardService());
        $this->commands([
            SyncCrmCustomers::class,
            FlagAbandonedCarts::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'crm');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components');
    }
}
