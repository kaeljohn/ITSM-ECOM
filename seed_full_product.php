<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$clientId = 13;

// 1. Procurement Supplier and Product
$supplierId = DB::connection('procurement')->table('suppliers')->where('client_id', $clientId)->where('name', 'Tech Parts Inc')->value('id');
if (!$supplierId) {
    $supplierId = DB::connection('procurement')->table('suppliers')->insertGetId([
        'client_id' => $clientId,
        'name' => 'Tech Parts Inc',
        'email' => 'sales@techparts.example.com',
        'phone' => '555-1234',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$supplierProductId = DB::connection('procurement')->table('supplier_products')->where('client_id', $clientId)->where('sku', 'GPU-5090-' . $clientId)->value('id');
if (!$supplierProductId) {
    DB::connection('procurement')->table('supplier_products')->insert([
        'client_id' => $clientId,
        'supplier_id' => $supplierId,
        'name' => 'Nvidia RTX 5090',
        'sku' => 'GPU-5090-' . $clientId,
        'unit_price' => 1500.00,
        'uom' => 'pcs',
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

// 2. Inventory Category and Item
$categoryId = DB::connection('inventory')->table('categories')->where('client_id', $clientId)->where('name', 'Components')->value('id');
if (!$categoryId) {
    $categoryId = DB::connection('inventory')->table('categories')->insertGetId([
        'client_id' => $clientId,
        'name' => 'Components',
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$inventoryItemId = DB::connection('inventory')->table('items')->where('client_id', $clientId)->where('sku', 'INV-GPU-5090-' . $clientId)->value('id');
if (!$inventoryItemId) {
    $inventoryItemId = DB::connection('inventory')->table('items')->insertGetId([
        'client_id' => $clientId,
        'category_id' => $categoryId,
        'name' => 'Nvidia RTX 5090 GPU',
        'sku' => 'INV-GPU-5090-' . $clientId,
        'unit_cost' => 1500.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

// 3. Inventory Warehouse and Stock
$warehouseId = DB::connection('inventory')->table('warehouses')->where('client_id', $clientId)->where('name', 'Main Warehouse')->value('id');
if (!$warehouseId) {
    $warehouseId = DB::connection('inventory')->table('warehouses')->insertGetId([
        'client_id' => $clientId,
        'name' => 'Main Warehouse',
        'capacity_units' => 1000,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

DB::connection('inventory')->table('stock_levels')->updateOrInsert(
    ['client_id' => $clientId, 'item_id' => $inventoryItemId, 'warehouse_id' => $warehouseId],
    ['stock' => 50, 'reserved_quantity' => 0, 'created_at' => now(), 'updated_at' => now()]
);

// 4. Manufacturing BOM
$bomId = DB::connection('manufacturing')->table('product_boms')->where('client_id', $clientId)->where('sku', 'SYS-TITAN-X')->value('id');
if (!$bomId) {
    $bomId = DB::connection('manufacturing')->table('product_boms')->insertGetId([
        'client_id' => $clientId,
        'sku' => 'SYS-TITAN-X',
        'name' => 'Titan X Ultimate PC',
        'description' => 'Flagship PC with RTX 5090',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

// 5. Manufacturing BOM Item
DB::connection('manufacturing')->table('product_bom_items')->updateOrInsert(
    ['client_id' => $clientId, 'bom_id' => $bomId, 'inventory_item_id' => $inventoryItemId],
    ['item_name' => 'Nvidia RTX 5090 GPU', 'quantity_required' => 1, 'created_at' => now(), 'updated_at' => now()]
);

echo "End-to-end product seeded successfully across Procurement, Inventory, and Manufacturing.\n";
