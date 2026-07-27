<?php

namespace Modules\Ecommerce\CRM\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\CRM\Console\Commands\SyncCrmCustomers;
use Modules\Ecommerce\CRM\Console\Commands\FlagAbandonedCarts;
use Modules\Ecommerce\CRM\Console\Commands\BatchEvaluateRfm;
use Modules\Ecommerce\CRM\Console\Commands\BatchEvaluateChurn;
use Modules\Ecommerce\CRM\Services\CrmDashboardService;
use Modules\Ecommerce\CRM\Services\LtvCalculator;
use Modules\Ecommerce\CRM\Services\ChurnRiskService;
use Modules\Ecommerce\CRM\Services\ActivityTimelineService;
use Modules\Ecommerce\CRM\Services\RfmSegmentEngine;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services as singletons so they share state within a request
        $this->app->singleton(CrmDashboardService::class);
        $this->app->singleton(LtvCalculator::class);
        $this->app->singleton(ChurnRiskService::class);
        $this->app->singleton(ActivityTimelineService::class);
        $this->app->singleton(RfmSegmentEngine::class);

        $this->commands([
            SyncCrmCustomers::class,
            FlagAbandonedCarts::class,
            BatchEvaluateRfm::class,
            BatchEvaluateChurn::class,
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
