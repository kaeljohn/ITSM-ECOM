<?php

namespace Modules\Manufacturing\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Manufacturing\Console\Commands\InstallManufacturingSchema;
use Modules\Manufacturing\Console\Commands\AssignManufacturingDataToClient;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([InstallManufacturingSchema::class]);
        $this->commands([AssignManufacturingDataToClient::class]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'manufacturing');
        // Manufacturing owns a separate connection. Register its migrations
        // here so a fresh Manufacturing database is initialized by the normal
        // deployment migration command rather than silently using Finance.
        $this->loadMigrationsFrom(__DIR__.'/../../database/manual-migrations');

        Route::middleware('web')->prefix('manufacturing')->group(__DIR__.'/../../routes/web.php');
    }
}
