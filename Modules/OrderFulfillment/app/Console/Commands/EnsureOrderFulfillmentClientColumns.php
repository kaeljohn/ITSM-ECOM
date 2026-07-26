<?php

namespace Modules\OrderFulfillment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureOrderFulfillmentClientColumns extends Command
{
    protected $signature = 'order-fulfillment:ensure-client-columns';

    protected $description = 'Add client boundaries to existing Order Fulfillment tables';

    public function handle(): int
    {
        $schema = Schema::connection('order_fulfillment');

        foreach (['orders', 'shipments', 'delivery_men', 'packing_errors', 'requisitions'] as $tableName) {
            if (! $schema->hasTable($tableName)) {
                continue;
            }

            if (! $schema->hasColumn($tableName, 'client_id')) {
                $schema->table($tableName, function (Blueprint $table): void {
                    // Legacy rows deliberately remain unassigned. They must not
                    // become visible to an arbitrary client after this upgrade.
                    $table->unsignedBigInteger('client_id')->nullable()->index();
                });
                $this->info("Added client_id to Order Fulfillment {$tableName}.");
            }
        }

        // Ensure the orders table has product_name (may be missing if created outside the migration).
        if ($schema->hasTable('orders') && ! $schema->hasColumn('orders', 'product_name')) {
            $schema->table('orders', function (Blueprint $table): void {
                $table->string('product_name')->default('Storefront order');
            });
            $this->info('Added product_name to Order Fulfillment orders table.');
        }

        return self::SUCCESS;
    }
}
