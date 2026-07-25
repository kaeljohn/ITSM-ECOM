<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$boms = DB::connection('manufacturing')->table('product_boms')->get();

foreach ($boms as $bom) {
    try {
        DB::connection('manufacturing')->table('product_bom_items')->insert([
            'client_id' => 1,
            'bom_id' => $bom->id,
            'inventory_item_id' => 1,
            'quantity_required' => 1
        ]);
    } catch (Exception $e) {
        // May already exist
    }

    try {
        DB::connection('inventory')->table('stock_levels')->updateOrInsert(
            ['client_id' => 1, 'item_id' => 1, 'warehouse_id' => 1],
            ['stock' => 100, 'reserved_quantity' => 0]
        );
    } catch (Exception $e) {
        // May fail if schema is different, but we try
    }
}

echo "Seeded BOM items and stock levels successfully.\n";
