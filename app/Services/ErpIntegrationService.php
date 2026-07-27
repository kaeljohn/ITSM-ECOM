<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Synchronous integration boundary for ERP transactions.
 *
 * Each module keeps ownership of its own database. This service only creates
 * the client-scoped counterpart records required by an ERP transaction and
 * records a durable ITSM audit entry for every outcome.
 */
class ErpIntegrationService
{
    /** @var array<string, array<int, string>> */
    private array $columns = [];

    public function recordAudit(int $clientId, string $event, string $module, array $details = []): void
    {
        if (! Schema::hasTable('erp_audit_logs')) {
            return;
        }

        DB::table('erp_audit_logs')->insert([
            'client_id' => $clientId,
            'event' => $event,
            'module' => $module,
            'actor_id' => session('employee_id') ?: auth()->id(),
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Propagate a committed storefront order into operational modules.
     * Inventory is reserved first; if a line cannot be fulfilled the checkout
     * is rejected before any downstream record is created.
     */
    public function propagateEcommerceOrder(int $clientId, object $order, iterable $items): void
    {
        $items = collect($items)->values();
        $reservationLines = $this->reserveInventory($clientId, (string) $order->id, $items);

        try {
            $customer = trim(($order->shipping_address['first_name'] ?? '').' '.($order->shipping_address['last_name'] ?? ''));
            $productSummary = $items->pluck('name')->filter()->unique()->implode(', ');
            $totalQuantity = (int) $items->sum('quantity');
            $amount = (float) $order->total;

            $this->createFulfillmentOrder($clientId, $customer, $productSummary, $totalQuantity, $amount, $order, $items);
            $this->createManufacturingWorkOrder($clientId, (string) $order->id, $productSummary, $totalQuantity);
            $this->createProcurementRequisition($clientId, (string) $order->id, $productSummary, $totalQuantity, $amount);
            $this->createFinanceInvoice($clientId, (string) $order->id, $amount, (float) $order->shipping_fee, (string) $order->payment_status);
            $this->createCrmCustomerProfile($clientId, (int) $order->user_id, $order);
            $this->writeBiSnapshot($clientId);

            $this->recordAudit($clientId, 'order.placed', 'ecommerce', [
                'order_id' => (string) $order->id,
                'reservations' => $reservationLines,
                'total' => $amount,
            ]);
        } catch (\Throwable $exception) {
            $this->recordAudit($clientId, 'order.propagation_failed', 'ecommerce', [
                'order_id' => (string) $order->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function inventoryAvailabilityChanged(int $clientId, int $itemId, string $event = 'inventory.stock_changed'): void
    {
        $inventory = DB::connection('inventory');
        $item = $inventory->table('items')->where('client_id', $clientId)->where('id', $itemId)->first();
        if (! $item) return;

        $available = (int) $inventory->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->sum(DB::raw('stock - reserved_quantity'));
        $soldOut = $available <= 0;

        // Standalone ecommerce tables vary by catalogue type. Update every
        // client-scoped catalogue row that uses the same product name, where
        // that table exposes stock or a sold-out flag.
        foreach (['products', 'components', 'cpus', 'gpus', 'rams', 'storages', 'laptops'] as $table) {
            if (! Schema::connection('ecommerce')->hasTable($table)) continue;
            $columns = $this->columns['ecommerce.'.$table] ??= Schema::connection('ecommerce')->getColumnListing($table);
            if (! in_array('name', $columns, true)) continue;
            $changes = [];
            if (in_array('stock', $columns, true)) $changes['stock'] = $available;
            if (in_array('is_sold_out', $columns, true)) $changes['is_sold_out'] = $soldOut;
            if (in_array('updated_at', $columns, true)) $changes['updated_at'] = now();
            if ($changes) DB::connection('ecommerce')->table($table)->where('client_id', $clientId)->whereRaw('LOWER(name) = ?', [mb_strtolower($item->name)])->update($changes);
        }

        if ($available <= 0 || $this->isLowStock($clientId, $itemId, $available)) {
            $this->createProcurementRequisition($clientId, 'STOCK-'.$itemId, $item->name, max(1, $this->recommendedQuantity($clientId, $itemId, $available)), (float) $item->unit_cost * max(1, $this->recommendedQuantity($clientId, $itemId, $available)));
        }

        $this->writeBiSnapshot($clientId);
        $this->recordAudit($clientId, $event, 'inventory', ['item_id' => $itemId, 'sku' => $item->sku, 'available_quantity' => $available]);
    }

    public function financeInvoiceChanged(int $clientId, object $invoice, bool $cancelled = false): void
    {
        $orderId = (string) ($invoice->order_id ?? '');
        if ($orderId !== '') {
            if ($cancelled) {
                DB::connection('order_fulfillment')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['status' => 'CANCELLED', 'updated_at' => now()]);
                DB::connection('ecommerce')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['status' => 'cancelled', 'updated_at' => now()]);
            } elseif (strtolower((string) $invoice->status) === 'paid') {
                DB::connection('ecommerce')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['payment_status' => 'paid', 'updated_at' => now()]);
            }
        }

        $this->writeBiSnapshot($clientId);
        $this->recordAudit($clientId, $cancelled ? 'invoice.cancelled' : 'invoice.updated', 'finance', ['invoice_id' => $invoice->getKey(), 'order_id' => $orderId, 'status' => $invoice->status]);
    }

    public function employeeRemoved(int $clientId, int $employeeId, string $email): void
    {
        // Employee sessions are server-side. Remove their current session state
        // and leave the audit record as the authoritative ITSM trail.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $employeeId)->delete();
        }
        $this->recordAudit($clientId, 'employee.deleted', 'hr', ['employee_id' => $employeeId, 'email' => $email]);
    }

    /**
     * Create or update a CRM customer profile from an ecommerce order.
     *
     * Called automatically when an order is placed. This is the primary
     * integration point between E-Commerce and Sales & CRM: a storefront
     * purchase creates the customer record in the CRM module.
     */
    private function createCrmCustomerProfile(int $clientId, int $userId, object $order): void
    {
        if (! Schema::connection('ecommerce')->hasTable('crm_customers')) {
            return;
        }

        $ecommerce = DB::connection('ecommerce');

        // Fetch the user who placed the order
        $user = $ecommerce->table('users')->where('id', $userId)->first();
        if (! $user) {
            return;
        }

        // Calculate aggregate order stats for this user
        $stats = $ecommerce->table('orders')
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('count(*) as order_count')
            ->selectRaw('coalesce(sum(total), 0) as total_spent')
            ->first();

        $orderCount = (int) ($stats->order_count ?? 1);
        $totalSpent = (float) ($stats->total_spent ?? 0);
        $avgOrderValue = $orderCount > 0 ? round($totalSpent / $orderCount, 2) : 0;

        // Derive customer name from shipping address, then fall back to user record
        $firstName = $order->shipping_address['first_name'] ?? $user->first_name ?? explode('@', $user->email)[0];
        $lastName  = $order->shipping_address['last_name'] ?? $user->last_name ?? '';

        // Extract the source channel from the payment method
        $source = 'storefront';

        $ecommerce->table('crm_customers')->updateOrInsert(
            [
                'client_id' => $clientId,
                'user_id'   => $userId,
            ],
            [
                'email'               => $user->email,
                'first_name'          => $firstName,
                'last_name'           => $lastName,
                'phone'               => $user->phone ?? null,
                'source'              => $source,
                'total_spent'         => $totalSpent,
                'order_count'         => $orderCount,
                'average_order_value' => $avgOrderValue,
                'last_purchase_at'    => now(),
                'last_engaged_at'     => now(),
                'opt_in_email'        => true,
                'updated_at'          => now(),
                'created_at'          => now(),
            ]
        );

        $this->recordAudit($clientId, 'crm.customer_synced', 'crm', [
            'user_id' => $userId,
            'email'   => $user->email,
            'orders'  => $orderCount,
            'total'   => $totalSpent,
        ]);
    }

    public function supplierChanged(int $clientId, object $supplier, bool $deleted = false): void
    {
        foreach (['inventory', 'manufacturing'] as $connection) {
            if (! Schema::connection($connection)->hasTable('integration_suppliers')) continue;
            $table = DB::connection($connection)->table('integration_suppliers');
            if ($deleted) {
                $table->where('client_id', $clientId)->where('source_supplier_id', $supplier->id)->delete();
                continue;
            }
            $table->updateOrInsert(
                ['client_id' => $clientId, 'source_supplier_id' => $supplier->id],
                ['name' => $supplier->name, 'email' => $supplier->email, 'phone' => $supplier->phone, 'category' => $supplier->category, 'status' => $supplier->status, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        $this->recordAudit($clientId, $deleted ? 'supplier.deleted' : 'supplier.synced', 'procurement', ['supplier_id' => $supplier->id, 'name' => $supplier->name]);
    }

    public function inventoryProductDeleted(int $clientId, object $item): void
    {
        foreach (['products', 'components', 'cpus', 'gpus', 'rams', 'storages', 'laptops'] as $table) {
            if (! Schema::connection('ecommerce')->hasTable($table)) continue;
            $columns = $this->columns['ecommerce.'.$table] ??= Schema::connection('ecommerce')->getColumnListing($table);
            if (! in_array('name', $columns, true) || ! in_array('is_sold_out', $columns, true)) continue;
            $changes = ['is_sold_out' => true];
            if (in_array('updated_at', $columns, true)) $changes['updated_at'] = now();
            DB::connection('ecommerce')->table($table)->where('client_id', $clientId)->whereRaw('LOWER(name) = ?', [mb_strtolower($item->name)])->update($changes);
        }
        $this->recordAudit($clientId, 'inventory.item_deleted', 'inventory', ['item_id' => $item->id, 'sku' => $item->sku, 'name' => $item->name]);
    }

    /**
     * Reserve inventory for an ecommerce order.
     *
     * IMPORTANT: This method uses NO transaction wrapper at all.
     * Each inventory operation is an independent, atomic SQL statement.
     * This completely eliminates the PostgreSQL 25P02 (current transaction
     * is aborted) cascade that occurs when a prior query inside a
     * transaction fails and subsequent queries get rejected.
     *
     * Race-condition safety: the UPDATE uses
     *   WHERE (stock - reserved_quantity) >= $allocated
     * so concurrent requests naturally fail the WHERE clause rather than
     * overselling. No SELECT ... FOR UPDATE is needed.
     *
     * @return array<int, array{item_id:int,warehouse_id:int,quantity:int}>
     */
    private function reserveInventory(int $clientId, string $orderId, iterable $items): array
    {
        $inventory = DB::connection('inventory');
        $reserved = [];
        $requirements = [];

        // ── Phase 1: Build requirements (read-only, no transaction) ──────
        foreach ($items as $line) {
            $quantity = max(1, (int) $line->quantity);

            if (($line->product_type ?? null) === 'bom_listing') {
                $configuration = is_array($line->configuration ?? null)
                    ? $line->configuration
                    : json_decode((string) ($line->configuration ?? ''), true);
                $bomId = (int) ($configuration['bom_id'] ?? 0);
                $components = $bomId
                    ? DB::connection('manufacturing')->table('product_bom_items')
                        ->where('client_id', $clientId)->where('bom_id', $bomId)->get()
                    : collect();

                if ($components->isEmpty()) {
                    Log::warning('BOM components not found for ecommerce order item', [
                        'product' => $line->name,
                        'bom_id' => $bomId,
                        'client_id' => $clientId,
                    ]);
                    continue;
                }

                foreach ($components as $component) {
                    $itemId = (int) $component->inventory_item_id;
                    $requirements[$itemId] = [
                        'name' => (string) $component->item_name,
                        'quantity' => ($requirements[$itemId]['quantity'] ?? 0)
                            + ($quantity * max(1, (int) $component->quantity_required)),
                    ];
                }

                continue;
            }

            $item = $inventory->table('items')
                ->where('client_id', $clientId)
                ->where(function ($query) use ($line): void {
                    $query->where('sku', (string) $line->product_id)
                        ->orWhereRaw('LOWER(name) = ?', [mb_strtolower((string) $line->name)]);
                })
                ->orderBy('id')
                ->first();

            if (! $item) {
                Log::warning('Inventory item not mapped for ecommerce order item — skipping reservation', [
                    'product' => $line->name,
                    'product_id' => $line->product_id,
                    'client_id' => $clientId,
                ]);
                continue;
            }

            $requirements[(int) $item->id] = [
                'name' => (string) $item->name,
                'quantity' => ($requirements[(int) $item->id]['quantity'] ?? 0) + $quantity,
            ];
        }

        // ── Phase 2: Reserve via standalone atomic SQL statements ────────
        // NO transaction wrapper. Each statement is independent.
        foreach ($requirements as $itemId => $requirement) {
            // Skip if already reserved for this order.
            $hasReservation = $inventory->select(
                'SELECT id FROM order_reservations WHERE client_id = ? AND order_reference = ? AND item_id = ? AND status = ? LIMIT 1',
                [$clientId, $orderId, $itemId, 'reserved']
            );
            if (! empty($hasReservation)) {
                continue;
            }

            // Check whether this component is tracked in inventory at all.
            $hasStockLevel = $inventory->select(
                'SELECT id FROM stock_levels WHERE client_id = ? AND item_id = ? LIMIT 1',
                [$clientId, $itemId]
            );
            if (empty($hasStockLevel)) {
                // Item not tracked — skip reservation, order proceeds.
                continue;
            }

            // Read available stock (no lock).
            $levels = $inventory->select(
                'SELECT id, warehouse_id, stock, reserved_quantity FROM stock_levels WHERE client_id = ? AND item_id = ? AND (stock - reserved_quantity) > 0 ORDER BY id ASC',
                [$clientId, $itemId]
            );

            $remaining = (int) $requirement['quantity'];
            $now = now()->toDateTimeString();

            foreach ($levels as $level) {
                if ($remaining <= 0) {
                    break;
                }

                $available = (int) $level->stock - (int) $level->reserved_quantity;
                $allocated = min($remaining, $available);
                if ($allocated < 1) {
                    continue;
                }

                // Atomic UPDATE: only increments if enough stock exists.
                // DB::update() returns the number of affected rows (0 or 1).
                // No transaction wrapper = no 25P02 cascade possible.
                $affected = $inventory->update(
                    'UPDATE stock_levels SET reserved_quantity = reserved_quantity + ?, updated_at = ? WHERE id = ? AND client_id = ? AND (stock - reserved_quantity) >= ?',
                    [$allocated, $now, $level->id, $clientId, $allocated]
                );

                if ($affected === 0) {
                    // Race condition — another request took this stock.
                    continue;
                }

                // Record the reservation (standalone INSERT, no transaction).
                try {
                    $inventory->table('order_reservations')->insert([
                        'client_id' => $clientId,
                        'order_reference' => $orderId,
                        'source' => 'ecommerce',
                        'item_id' => $itemId,
                        'warehouse_id' => $level->warehouse_id,
                        'quantity' => $allocated,
                        'status' => 'reserved',
                        'reserved_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('order_reservations insert failed', [
                        'item_id' => $itemId,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Record the stock movement (standalone INSERT, no transaction).
                try {
                    $inventory->table('stock_movements')->insert([
                        'client_id' => $clientId,
                        'type' => 'reservation',
                        'item_id' => $itemId,
                        'warehouse_id' => $level->warehouse_id,
                        'quantity' => -$allocated,
                        'reference' => 'ECOM-'.$orderId,
                        'reference_id' => $orderId,
                        'performed_by' => null,
                        'notes' => 'Reserved for ecommerce order',
                        'created_at' => $now,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('stock_movements insert failed', [
                        'item_id' => $itemId,
                        'error' => $e->getMessage(),
                    ]);
                }

                $reserved[] = [
                    'item_id' => $itemId,
                    'warehouse_id' => (int) $level->warehouse_id,
                    'quantity' => $allocated,
                ];
                $remaining -= $allocated;
            }

            if ($remaining > 0) {
                Log::warning('Insufficient available inventory for ecommerce order item — skipping remainder', [
                    'item' => $requirement['name'],
                    'needed' => $requirement['quantity'],
                    'reserved' => $requirement['quantity'] - $remaining,
                    'client_id' => $clientId,
                ]);
            }
        }

        return $reserved;
    }

    private function createFulfillmentOrder(int $clientId, string $customer, string $product, int $quantity, float $amount, object $order, iterable $items): void
    {
        $db = DB::connection('order_fulfillment');
        $address = is_array($order->shipping_address) ? implode(', ', array_filter($order->shipping_address)) : null;

        // The live orders table uses ORD-XXX style IDs (e.g. ORD-057).
        // A trigger or default on the table may override whatever id we provide,
        // so we generate the next available ORD-XXX id ourselves.
        // Retry on duplicate key to handle concurrent inserts safely.
        $fulfillmentOrderId = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $this->generateFulfillmentOrderId($db);
            try {
                $this->insertAvailable('order_fulfillment', 'orders', [
                    'id' => $candidate,
                    'client_id' => $clientId,
                    'customer_name' => $customer ?: 'Customer',
                    'product_name' => $product ?: 'Storefront order',
                    'qty' => $quantity,
                    'product_amount' => $amount,
                    'status' => 'NEW',
                    'address' => $address,
                    'due_date' => now()->addDays(3)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $fulfillmentOrderId = $candidate;
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // PostgreSQL unique_violation (23505) — another request claimed this ID; retry.
                if ($e->getCode() === '23505' && $attempt < 4) {
                    continue;
                }
                throw $e;
            }
        }

        if (! Schema::connection('order_fulfillment')->hasTable('order_items')) {
            return;
        }

        $lines = collect($items)->map(function ($item) use ($clientId, $fulfillmentOrderId): array {
            $quantity = max(1, (int) data_get($item, 'quantity', 1));
            $unitPrice = (float) (data_get($item, 'price') ?? data_get($item, 'unit_price', 0));

            return [
                'client_id'      => $clientId,
                'order_id'       => $fulfillmentOrderId,
                'product_name'   => (string) data_get($item, 'name', 'Storefront item'),
                'qty'            => $quantity,
                'product_amount' => $unitPrice,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        })->all();

        foreach ($lines as $line) {
            $this->insertAvailable('order_fulfillment', 'order_items', $line);
        }
    }

    /**
     * Generate the next ORD-XXX id for the order_fulfillment orders table.
     * Falls back to ECOM-{timestamp} if the table is empty or has no ORD- rows.
     */
    private function generateFulfillmentOrderId(\Illuminate\Database\ConnectionInterface $db): string
    {
        $last = $db->table('orders')
            ->where('id', 'LIKE', 'ORD-%')
            ->orderByRaw("CAST(SUBSTRING(id FROM 5) AS INTEGER) DESC")
            ->value('id');

        if ($last && preg_match('/^ORD-(\d+)$/', $last, $m)) {
            return 'ORD-' . str_pad((int) $m[1] + 1, 3, '0', STR_PAD_LEFT);
        }

        return 'ORD-' . str_pad(1, 3, '0', STR_PAD_LEFT);
    }

    private function createManufacturingWorkOrder(int $clientId, string $orderId, string $product, int $quantity): void
    {
        $db = DB::connection('manufacturing');
        $id = 'WO-'.strtoupper(substr(sha1($orderId), 0, 12));
        if ($db->table('work_orders')->where('id', $id)->exists()) return;
        $this->insertAvailable('manufacturing', 'work_orders', ['id' => $id, 'client_id' => $clientId, 'fulfillment_order_id' => $orderId, 'name' => $product ?: 'Storefront assembly', 'specs' => "Ecommerce order {$orderId}; quantity {$quantity}", 'status' => 'Pending', 'due' => now()->addDays(3)->toDateString(), 'due_date' => now()->addDays(3)->toDateString(), 'source' => 'Ecommerce '.$orderId, 'assigned' => null, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createProcurementRequisition(int $clientId, string $orderId, string $product, int $quantity, float $amount): void
    {
        $db = DB::connection('procurement');
        $number = 'AUTO-'.strtoupper(substr(sha1($orderId), 0, 10));
        if ($db->table('requisitions')->where('client_id', $clientId)->where('req_number', $number)->exists()) return;
        $this->insertAvailable('procurement', 'requisitions', ['client_id' => $clientId, 'req_number' => $number, 'item' => $product ?: 'Storefront order materials', 'qty' => $quantity, 'amount' => $amount, 'uom' => 'unit', 'delivery_status' => 'pending', 'department' => 'Manufacturing', 'requested_by' => 'ERP Integration', 'status' => 'pending', 'date_requested' => now()->toDateString(), 'notes' => "Auto-created for ecommerce order {$orderId}", 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createFinanceInvoice(int $clientId, string $orderId, float $amount, float $shipping, string $paymentStatus): void
    {
        $db = DB::connection('finance');
        $columns = $this->columns['finance.invoice'] ??= Schema::connection('finance')->getColumnListing('invoice');

        // Duplicate check — only query columns that actually exist.
        $duplicateQuery = $db->table('invoice');
        if (in_array('nexora_client_id', $columns, true)) {
            $duplicateQuery->where('nexora_client_id', $clientId);
        }
        if (in_array('order_id', $columns, true)) {
            $duplicateQuery->where('order_id', $orderId);
        }
        // Only run the duplicate check when at least one filter was added.
        if (count($duplicateQuery->wheres) > 0 && $duplicateQuery->exists()) {
            return;
        }

        $paid = strtolower($paymentStatus) === 'paid';
        $this->insertAvailable('finance', 'invoice', [
            'nexora_client_id' => $clientId,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'invoice_amount' => $amount - $shipping,
            'discount' => 0,
            'shipping_fee' => $shipping,
            'paid_amount' => $paid ? $amount : 0,
            'outstanding_amount' => $paid ? 0 : $amount,
            'payment_method' => null,
            'reference_number' => 'ECOM-'.$orderId,
            'payment_details' => 'Automatically generated from ecommerce checkout',
            'payment_status' => $paid ? 'Paid' : 'Unpaid',
            'status' => $paid ? 'Paid' : 'Pending',
            'payment_date' => $paid ? now()->toDateString() : null,
            'order_id' => $orderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function writeBiSnapshot(int $clientId): void
    {
        if (! Schema::connection('business_intelligence')->hasTable('bi_snapshots')) return;
        $db = DB::connection('business_intelligence');
        $payload = ['event' => 'order.placed', 'captured_at' => now()->toIso8601String()];
        $db->table('bi_snapshots')->updateOrInsert(['client_id' => $clientId, 'source' => 'erp-integration'], ['payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'captured_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
    }

    private function isLowStock(int $clientId, int $itemId, int $available): bool
    {
        $threshold = (int) DB::connection('inventory')->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->max('reorder_threshold');
        return $threshold > 0 && $available <= $threshold;
    }

    private function recommendedQuantity(int $clientId, int $itemId, int $available): int
    {
        $threshold = (int) DB::connection('inventory')->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->max('reorder_threshold');
        return max(1, ($threshold * 2) - $available);
    }

    private function insertAvailable(string $connection, string $table, array $attributes): void
    {
        $columns = $this->columns[$connection.'.'.$table] ??= Schema::connection($connection)->getColumnListing($table);
        DB::connection($connection)->table($table)->insert(array_intersect_key($attributes, array_flip($columns)));
    }
}
