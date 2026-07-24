<?php

namespace Modules\Procurement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Models\Warehouse;

class ProcurementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'procurement');
        $this->commands([
            \Modules\Procurement\Console\Commands\EnsureProcurementClientColumns::class,
            \Modules\Procurement\Console\Commands\InstallProcurementSchema::class,
        ]);

        Route::middleware('web')
            ->prefix('procurement')
            ->name('procurement.')
            ->group(__DIR__.'/../../routes/web.php');

        // The PO modal is included by every Procurement page, not just the
        // purchase-order page. Supply its client-scoped warehouse list at the
        // shared partial level so a user cannot submit a PO with a typed or
        // foreign-client delivery address.
        View::composer('procurement::partials.modals', function ($view): void {
            $view->with('warehouses', Warehouse::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'address']));
        });

          View::composer('procurement::partials.sidebar', function ($view): void {
            $alerts = DB::connection('inventory')
                ->table('stock_levels as sl')
                ->join('items as i', 'sl.item_id', '=', 'i.id')
                ->where('sl.stock', '<', 5)
                ->orderBy('sl.stock', 'asc')
                ->select('sl.stock', 'sl.reorder_threshold', 'i.name as item_name', 'i.sku')
                ->limit(5)
                ->get();

            $requisitionCount = $this->pendingRequisitionCount();

            $view->with([
                'lowStockAlerts' => $alerts,
                'lowStockAlertCount' => $alerts->count(),
                'requisitionCount' => $requisitionCount,
            ]);
        });
    }

    private function pendingRequisitionCount(): int
    {
        $count = 0;
        $clientId = (int) session('employee_client_id');
        $rootTesting = config('nexora.root_admin_module_testing')
            && auth()->user()?->role === 'root_admin';

        foreach (['order_fulfillment', 'manufacturing'] as $connectionName) {
            try {
                $connection = DB::connection($connectionName);
                $schema = $connection->getSchemaBuilder();
                if (! $schema->hasTable('requisitions')) {
                    continue;
                }

                $query = $connection->table('requisitions');
                if (! $rootTesting && $schema->hasColumn('requisitions', 'client_id')) {
                    $query->where('client_id', $clientId);
                }

                if ($schema->hasColumn('requisitions', 'status')) {
                    $query->whereRaw('LOWER(status) = ?', ['pending']);
                }

                $count += $query->count();
            } catch (\Exception $e) {
                // ignore broken or unavailable external DB connections
            }
        }

        return $count;
    }
}
