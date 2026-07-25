<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

use Modules\Ecommerce\Models\Order;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();

        $paymentMethods = $user->paymentMethods()->orderBy('is_default', 'desc')->get();
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get();

        $orders = collect();

        if ($user) {
            // Fetch ecommerce orders for this user
            $ecomOrders = Order::with('items')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $ecomOrderIds = $ecomOrders->pluck('id')->filter()->all();

            $fulfillmentOrders = collect();
            $shipments = collect();

            // Always fetch fulfillment orders that match by ID
            if (!empty($ecomOrderIds)) {
                $fulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->whereIn('id', $ecomOrderIds)
                    ->get()
                    ->keyBy('id');

                $shipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $ecomOrderIds)
                    ->get()
                    ->keyBy('order_id');
            }

            // Also fetch fulfillment orders that match by customer_name but DON'T have a matching ecom order ID
            $linkedFulfillmentIds = $fulfillmentOrders->pluck('id')->all();
            $unmatchedFulfillmentOrders = DB::connection('order_fulfillment')
                ->table('orders')
                ->where('customer_name', 'LIKE', '%' . $user->name . '%')
                ->whereNotIn('id', array_merge($ecomOrderIds, $linkedFulfillmentIds))
                ->latest()
                ->get();

            $unmatchedShipmentIds = $unmatchedFulfillmentOrders->pluck('id')->all();
            $unmatchedShipments = collect();
            if (!empty($unmatchedShipmentIds)) {
                $unmatchedShipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $unmatchedShipmentIds)
                    ->get()
                    ->keyBy('order_id');
            }

            // Build the merged orders collection
            if ($ecomOrders->isNotEmpty()) {
                foreach ($ecomOrders as $order) {
                    $fo = $fulfillmentOrders->get($order->id);
                    $shipment = $shipments->get($order->id);

                    $order->fulfillment_status = strtoupper($fo->status ?? $order->status ?? 'NEW');
                    $order->fulfillment_details = $fo;
                    $order->shipment_details = $shipment;

                    // Parse shipping_address JSON for use in the view/modal
                    $addr = $order->shipping_address;
                    if (is_string($addr)) {
                        $addr = json_decode($addr, true);
                    }
                    $order->shipping_address_parsed = $addr;

                    // If fulfillment DB has address but ecom doesn't, use fulfillment address
                    if (empty($addr) && $fo && isset($fo->address)) {
                        $order->shipping_address_parsed = ['raw' => $fo->address];
                    }

                    $orders->push($order);
                }
            }

            // Append unmatched fulfillment-only orders (created directly in fulfillment DB)
            foreach ($unmatchedFulfillmentOrders as $fo) {
                $fakeOrder = new Order();
                $fakeOrder->id = $fo->id;
                $fakeOrder->user_id = $user->id;
                $fakeOrder->total = $fo->product_amount;
                $fakeOrder->status = strtolower($fo->status);
                $fakeOrder->created_at = Carbon::parse($fo->created_at);
                $fakeOrder->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));

                $fakeItem = (object)[
                    'name' => $fo->product_name,
                    'price' => $fo->product_amount,
                    'quantity' => $fo->qty
                ];
                $fakeOrder->setRelation('items', collect([$fakeItem]));

                $fakeOrder->fulfillment_status = strtoupper($fo->status);
                $fakeOrder->fulfillment_details = $fo;
                $fakeOrder->shipment_details = $unmatchedShipments->get($fo->id);
                $fakeOrder->shipping_address_parsed = ['raw' => $fo->address ?? ''];

                $orders->push($fakeOrder);
            }
        }

        return view('ecommerce::account.index', compact('paymentMethods', 'addresses', 'orders'));
    }

    public function orderHistory(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();
        $orders = collect();

        if ($user) {
            $ecomOrders = Order::with('items')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $ecomOrderIds = $ecomOrders->pluck('id')->filter()->all();

            $fulfillmentOrders = collect();
            $shipments = collect();

            if (!empty($ecomOrderIds)) {
                $fulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->whereIn('id', $ecomOrderIds)
                    ->get()
                    ->keyBy('id');

                $shipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $ecomOrderIds)
                    ->get()
                    ->keyBy('order_id');
            }

            if ($ecomOrders->isEmpty()) {
                $legacyFulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->where('customer_name', 'LIKE', '%' . $user->name . '%')
                    ->latest()
                    ->get();

                if ($legacyFulfillmentOrders->isNotEmpty()) {
                    $legacyShipments = DB::connection('order_fulfillment')
                        ->table('shipments')
                        ->whereIn('order_id', $legacyFulfillmentOrders->pluck('id'))
                        ->get()
                        ->keyBy('order_id');

                    foreach ($legacyFulfillmentOrders as $fo) {
                        $fakeOrder = new Order();
                        $fakeOrder->id = $fo->id;
                        $fakeOrder->user_id = $user->id;
                        $fakeOrder->total = $fo->product_amount;
                        $fakeOrder->status = strtolower($fo->status);
                        $fakeOrder->created_at = Carbon::parse($fo->created_at);
                        $fakeOrder->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));
                        
                        $fakeItem = (object)[
                            'name' => $fo->product_name,
                            'price' => $fo->product_amount,
                            'quantity' => $fo->qty
                        ];
                        $fakeOrder->setRelation('items', collect([$fakeItem]));

                        $fakeOrder->fulfillment_status = strtoupper($fo->status);
                        $fakeOrder->fulfillment_details = $fo;
                        $fakeOrder->shipment_details = $legacyShipments->get($fo->id);

                        $orders->push($fakeOrder);
                    }
                }
            } else {
                foreach ($ecomOrders as $order) {
                    $fo = $fulfillmentOrders->get($order->id);
                    $shipment = $shipments->get($order->id);

                    $order->fulfillment_status = strtoupper($fo->status ?? $order->status ?? 'NEW');
                    $order->fulfillment_details = $fo;
                    $order->shipment_details = $shipment;

                    $orders->push($order);
                }
            }
        }

        return redirect()->to(route('ecommerce.account.profile') . '#order-history');
    }

    public function showOrder(Request $request, $id)
    {
        $user = Auth::guard('ecommerce')->user();

        $order = Order::with('items')->where('user_id', $user->id)->where('id', $id)->first();

        $fo = DB::connection('order_fulfillment')->table('orders')->where('id', $id)->first();
        $shipment = DB::connection('order_fulfillment')->table('shipments')->where('order_id', $id)->first();

        if (!$order && $fo) {
            // Legacy / demo order fallback
            $order = new Order();
            $order->id = $fo->id;
            $order->user_id = $user->id;
            $order->total = $fo->product_amount;
            $order->status = strtolower($fo->status);
            $order->created_at = Carbon::parse($fo->created_at);
            $order->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));
            $order->shipping_address = ['address' => $fo->address, 'name' => $fo->customer_name];
            
            $fakeItem = (object)[
                'name' => $fo->product_name,
                'price' => $fo->product_amount,
                'quantity' => $fo->qty,
                'configuration' => null,
            ];
            $order->setRelation('items', collect([$fakeItem]));
        }

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->fulfillment_status = strtoupper($fo->status ?? $order->status ?? 'NEW');
        $order->fulfillment_details = $fo;
        $order->shipment_details = $shipment;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        }

        return view('ecommerce::account.order-detail', compact('order'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender' => 'nullable|in:male,female,other',
            'dob_year' => 'nullable|integer',
            'dob_month' => 'nullable|integer',
            'dob_day' => 'nullable|integer',
        ]);

        $user->name = $validated['name'] ?? null;
        $user->email = $validated['email'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        $user->gender = $validated['gender'] ?? null;

        if (!empty($validated['dob_year']) && !empty($validated['dob_month']) && !empty($validated['dob_day'])) {
            try {
                $user->dob = Carbon::createFromDate($validated['dob_year'], $validated['dob_month'], $validated['dob_day'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Invalid date
            }
        }

        $user->save();

        return redirect()->route('ecommerce.account.profile')->with('success', 'Profile updated successfully!');
    }
}
