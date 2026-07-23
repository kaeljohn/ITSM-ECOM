<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Inventory\Console\Commands\EnsureInventoryHrActorColumns::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'inventory');
        Route::middleware('web')
            ->prefix('inventory')
            ->group(__DIR__.'/../../routes/web.php');
    }
}
