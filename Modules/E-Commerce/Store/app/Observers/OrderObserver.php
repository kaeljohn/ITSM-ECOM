<?php

namespace Modules\Ecommerce\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Order;

class OrderObserver
{
    /**
     * After an order is created, sync the customer's CRM profile.
     */
    public function created(Order $order): void
    {
        try {
            $user = DB::connection('ecommerce')->table('users')->find($order->user_id);
            if (! $user) return;

            $orderStats = DB::connection('ecommerce')->table('orders')
                ->where('user_id', $order->user_id)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('count(*) as order_count')
                ->selectRaw('coalesce(sum(total), 0) as total_spent')
                ->first();

            // Insert or update the CRM customer profile
            $table = 'crm_customers';
            $exists = DB::connection('ecommerce')->table($table)
                ->where('user_id', $order->user_id)
                ->exists();

            if ($exists) {
                DB::connection('ecommerce')->table($table)
                    ->where('user_id', $order->user_id)
                    ->update([
                        'total_spent' => $orderStats->total_spent ?? 0,
                        'order_count' => $orderStats->order_count ?? 0,
                        'average_order_value' => ($orderStats->order_count ?? 0) > 0
                            ? round(($orderStats->total_spent ?? 0) / $orderStats->order_count, 2)
                            : 0,
                        'last_purchase_at' => now(),
                        'email' => $user->email,
                        'first_name' => $user->first_name ?? explode('@', $user->email)[0],
                        'phone' => $user->phone ?? null,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::connection('ecommerce')->table($table)->insert([
                    'client_id' => $user->client_id ?? $order->client_id ?? null,
                    'user_id' => $order->user_id,
                    'email' => $user->email,
                    'first_name' => $user->first_name ?? explode('@', $user->email)[0],
                    'last_name' => $user->last_name ?? null,
                    'phone' => $user->phone ?? null,
                    'source' => 'direct',
                    'total_spent' => $orderStats->total_spent ?? $order->total,
                    'order_count' => $orderStats->order_count ?? 1,
                    'average_order_value' => $order->total ?? 0,
                    'last_purchase_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Don't let CRM sync failures break the order placement
            \Illuminate\Support\Facades\Log::warning('CRM customer sync failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }
}
