<?php

namespace Modules\Inventory\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Procurement extends Model
{
    protected $connection = 'procurement';
    protected $table = 'deliveries';

    protected $fillable = [
        'shipment_number',
        'purchase_order_id',
        'supplier_id',
        'stage',
        'status',
        'qty',
        'qty_expected',
        'items',
        'started_at',
        'remarks',
        'delivery_date',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'started_at' => 'datetime',
    ];

    public function getSupplierProduct(): ?object
    {
        $connection = DB::connection('procurement');
        $schema = $connection->getSchemaBuilder();

        if (! $this->purchase_order_id || ! $schema->hasTable('purchase_order_items')) {
            return $this->fallbackProduct();
        }

        $poItem = $connection->table('purchase_order_items')
            ->where('purchase_order_id', $this->purchase_order_id)
            ->first();

        if (!$poItem) {
            return $this->fallbackProduct();
        }

        $sku = null;

        if ($schema->hasTable('supplier_products')
            && property_exists($poItem, 'supplier_product_id')
            && ! empty($poItem->supplier_product_id)) {
            $supplierProduct = $connection
                ->table('supplier_products')
                ->where('id', $poItem->supplier_product_id)
                ->first();

            if ($supplierProduct) {
                $sku = $supplierProduct->sku;
            }
        }

        if (!$sku && $schema->hasTable('supplier_products')) {
            $productName = trim(preg_replace('/\s*@\s*.*$/', '', (string) ($poItem->name ?? $this->items ?? '')));
            $supplierProduct = $connection
                ->table('supplier_products')
                ->where('name', $productName)
                ->orWhere('name', 'ILIKE', $productName)
                ->first();

            if ($supplierProduct) {
                $sku = $supplierProduct->sku;
            }
        }

        if (!$sku) {
            $sku = 'AUTO-' . strtoupper(Str::random(8));
        }

        $cleanName = trim(preg_replace('/\s*@\s*.*$/', '', (string) ($poItem->name ?? $this->items ?? '')));

        return (object) [
            'item_name' => $cleanName,
            'qty' => (int) ($poItem->qty ?? $this->qty ?? 0),
            'unit_price' => (float) ($poItem->unit_price ?? 0),
            'sku' => $sku,
        ];
    }

    private function fallbackProduct(): ?object
    {
        $itemName = trim((string) ($this->items ?? ''));

        if ($itemName === '') {
            return null;
        }

        return (object) [
            'item_name' => $itemName,
            'qty' => (int) ($this->qty ?? 0),
            'unit_price' => 0,
            'sku' => 'AUTO-' . strtoupper(Str::random(8)),
        ];
    }
}
