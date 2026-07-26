<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedStockLevelsForListing extends Command
{
    protected $signature = 'ecommerce:seed-stock-levels
                           {clientId : The ITSM client/company ID}
                           {--listing= : The StorefrontListing ID to seed stock for}
                           {--name= : The StorefrontListing name to search for (used if --listing is omitted)}
                           {--warehouse= : Warehouse ID; defaults to the first active warehouse for this client}
                           {--quantity=100 : Initial stock quantity to set}';

    protected $description = 'Creates inventory items and stock_levels for a StorefrontListing\'s BOM components';

    public function handle(): int
    {
        try {
            return $this->handleInternal();
        } catch (\Throwable $e) {
            $this->error('ERROR: ' . get_class($e) . ': ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->line('');
            $this->line('Trace (top 5):');
            $trace = $e->getTrace();
            for ($i = 0; $i < min(5, count($trace)); $i++) {
                $t = $trace[$i];
                $this->line('  #' . $i . ' ' . ($t['file'] ?? 'unknown') . ':' . ($t['line'] ?? '?'));
            }

            return self::FAILURE;
        }
    }

    private function handleInternal(): int
    {
        $clientId = (int) $this->argument('clientId');

        if ($clientId < 1) {
            $this->error('clientId must be a positive integer.');

            return self::FAILURE;
        }

        $quantity = (int) $this->option('quantity');

        // ---- Resolve the listing ----
        $listingId = $this->option('listing');
        $listingName = $this->option('name');

        if ($listingId) {
            $listing = DB::connection('ecommerce')
                ->table('storefront_listings')
                ->where('client_id', $clientId)
                ->where('id', $listingId)
                ->first();
        } elseif ($listingName) {
            $listing = DB::connection('ecommerce')
                ->table('storefront_listings')
                ->where('client_id', $clientId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($listingName)])
                ->first();

            if (! $listing) {
                $listing = DB::connection('ecommerce')
                    ->table('storefront_listings')
                    ->where('client_id', $clientId)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($listingName) . '%'])
                    ->first();
            }
        } else {
            $this->error('Provide either --listing (ID) or --name to identify the StorefrontListing.');

            return self::FAILURE;
        }

        if (! $listing) {
            $existing = DB::connection('ecommerce')
                ->table('storefront_listings')
                ->where('client_id', $clientId)
                ->get(['id', 'name']);
            $this->error('StorefrontListing not found for client ' . $clientId . '.');
            if ($existing->isNotEmpty()) {
                $this->line('Existing listings for this client:');
                foreach ($existing as $e) {
                    $this->line("  ID {$e->id}: {$e->name}");
                }
            } else {
                $this->warn('No storefront_listings exist for client ' . $clientId . ' at all.');
            }

            return self::FAILURE;
        }

        $this->line('✓ Found listing: ' . $listing->name . ' (ID ' . $listing->id . ', BOM ' . $listing->bom_id . ')');

        // ---- Resolve BOM components ----
        $components = DB::connection('manufacturing')
            ->table('product_bom_items')
            ->where('client_id', $clientId)
            ->where('bom_id', $listing->bom_id)
            ->get(['id', 'inventory_item_id', 'item_sku', 'item_name', 'quantity_required']);

        if ($components->isEmpty()) {
            $this->warn('No BOM components found for BOM ' . $listing->bom_id . ' on the manufacturing connection.');
            $this->line('Verify that product_bom_items has rows for bom_id=' . $listing->bom_id . ', client_id=' . $clientId);
            $this->line('Check: DB::connection("manufacturing")->table("product_bom_items")->where("bom_id", ' . $listing->bom_id . ')->get()');

            return self::SUCCESS;
        }

        $this->line('✓ Found ' . $components->count() . ' BOM component(s)');

        // ---- Resolve warehouse ----
        $warehouseId = $this->option('warehouse');
        if (! $warehouseId) {
            $defaultWarehouse = DB::connection('inventory')
                ->table('warehouses')
                ->where('client_id', $clientId)
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

            if (! $defaultWarehouse) {
                $this->error('No active warehouse found for client ' . $clientId . '.');
                $this->line('Create one first, then pass --warehouse=<id>.');
                $all = DB::connection('inventory')->table('warehouses')->where('client_id', $clientId)->get();
                if ($all->isNotEmpty()) {
                    $this->line('Existing warehouses:');
                    foreach ($all as $w) {
                        $this->line('  ID ' . $w->id . ': ' . $w->name . ' (status=' . $w->status . ')');
                    }
                } else {
                    $this->warn('No warehouses exist for client ' . $clientId . '. Run this SQL first:');
                    $this->line('  INSERT INTO inventory.warehouses (client_id, name, capacity_units, status, created_at, updated_at)');
                    $this->line('  VALUES (' . $clientId . ', \'Main Warehouse\', 10000, \'active\', NOW(), NOW());');
                }

                return self::FAILURE;
            }

            $warehouseId = $defaultWarehouse->id;
            $this->line('✓ Using warehouse: ' . $defaultWarehouse->name . ' (ID ' . $warehouseId . ')');
        } else {
            $warehouseId = (int) $warehouseId;
        }

        // ---- Resolve default category ----
        $defaultCategory = DB::connection('inventory')
            ->table('categories')
            ->where('client_id', $clientId)
            ->orderBy('id')
            ->first();

        if (! $defaultCategory) {
            $this->warn('No categories found for client ' . $clientId . '. Creating a default one.');
            DB::connection('inventory')->statement(
                'INSERT INTO categories (client_id, name, created_at, updated_at)
                 VALUES (?, \'E-Commerce Products\', NOW(), NOW())',
                [$clientId]
            );
            $defaultCategory = DB::connection('inventory')
                ->table('categories')
                ->where('client_id', $clientId)
                ->where('name', 'E-Commerce Products')
                ->first();
            $this->line('✓ Created default category: ' . $defaultCategory->name . ' (ID ' . $defaultCategory->id . ')');
        }

        // ---- Seed each component ----
        $created = 0;
        $skipped = 0;

        foreach ($components as $component) {
            $itemId = (int) $component->inventory_item_id;

            $this->line('  Processing: ' . $component->item_name . ' (inventory_item_id=' . $itemId . ')');

            // Check if inventory item exists
            $item = DB::connection('inventory')
                ->table('items')
                ->where('client_id', $clientId)
                ->where('id', $itemId)
                ->first();

            if (! $item) {
                $sku = $component->item_sku ?? ('BOM-' . $listing->bom_id . '-' . $itemId);

                DB::connection('inventory')->statement(
                    'INSERT INTO items (id, client_id, sku, name, category_id, unit_cost, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())
                     ON CONFLICT (id) DO NOTHING',
                    [$itemId, $clientId, $sku, $component->item_name, $defaultCategory->id]
                );

                $this->line('    ✓ Created inventory item: ' . $component->item_name . ' (ID ' . $itemId . ', SKU ' . $sku . ')');
            } else {
                $this->line('    → Inventory item already exists (ID ' . $item->id . ', SKU ' . $item->sku . ')');
            }

            // Check if stock_levels already exist
            $existing = DB::connection('inventory')
                ->table('stock_levels')
                ->where('client_id', $clientId)
                ->where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if ($existing) {
                $this->line('    → stock_levels already exist (ID ' . $existing->id . ', stock=' . $existing->stock . ')');
                $skipped++;
                continue;
            }

            DB::connection('inventory')->statement(
                'INSERT INTO stock_levels (client_id, item_id, warehouse_id, stock, reserved_quantity, reorder_threshold, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 0, 10, NOW(), NOW())',
                [$clientId, $itemId, $warehouseId, $quantity]
            );

            $this->line('    ✓ Created stock_levels: ' . $quantity . ' units in warehouse ' . $warehouseId);
            $created++;
        }

        $this->info('Done. Created ' . $created . ' stock_levels entries, skipped ' . $skipped . ' existing.');

        return self::SUCCESS;
    }
}
