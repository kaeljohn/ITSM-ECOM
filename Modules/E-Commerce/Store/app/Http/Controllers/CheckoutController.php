<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Order;
use App\Services\ErpIntegrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Support\EcommerceClientContext;

class CheckoutController extends Controller
{
    public function index()
    {
        if (!Auth::guard('ecommerce')->check()) {
            session()->put('redirect_after_auth', route('ecommerce.checkout.index'));
            return redirect()->route('ecommerce.login');
        }

        $user = Auth::guard('ecommerce')->user();

        // Fetch saved addresses and payment methods
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get();
        $paymentMethods = $user->paymentMethods()->orderBy('is_default', 'desc')->get();

        // Require at least one saved address to proceed
        if ($addresses->isEmpty()) {
            return redirect()->route('ecommerce.account.profile')
                ->with('error', 'Please add a delivery address in your account before checking out.');
        }

        $cart = Cart::with('items')->where('user_id', Auth::guard('ecommerce')->id())->first();
        
        $cartItems = [];
        if ($cart) {
            foreach ($cart->items as $item) {
                $cartItems[] = [
                    'id' => $item->product_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'image_url' => $item->image_url,
                    'product_type' => $item->product_type,
                    'configuration' => $item->configuration,
                ];
            }
        }

        if (count($cartItems) === 0) {
            return redirect()->route('ecommerce.cart')->with('error', 'Your cart is empty.');
        }

        $subtotal = collect($cartItems)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });

        // Simple default shipping fee
        $shipping = 150; 
        $discount = 0;
        $total = $subtotal + $shipping - $discount;

        return view('ecommerce::checkout', compact('cartItems', 'subtotal', 'shipping', 'discount', 'total', 'addresses', 'paymentMethods'));
    }

    public function process(Request $request)
    {
        if (!Auth::guard('ecommerce')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('ecommerce')->user();

        $request->validate([
            'addressId' => 'required|integer',
            'shippingMethod' => 'required|string',
            'paymentMethod' => 'required|string',
        ]);

        // Resolve the saved address
        $address = $user->addresses()->where('id', $request->addressId)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Selected address not found.'], 422);
        }

        // Resolve payment method (skip for COD)
        $paymentLabel = $request->paymentMethod;
        $paymentMethodRecord = null;
        if ($request->paymentMethod !== 'cod') {
            $paymentMethodRecord = $user->paymentMethods()->where('id', $request->paymentMethod)->first();
            if (!$paymentMethodRecord) {
                return response()->json(['success' => false, 'message' => 'Selected payment method not found.'], 422);
            }
            $paymentLabel = $paymentMethodRecord->type . ' ending in ' . substr($paymentMethodRecord->account_number_mask ?? '', -4);
        }

        $cart = Cart::with('items')->where('user_id', $user->id)->first();
        if (!$cart || $cart->items->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $subtotal = $cart->items->sum(function($item) {
            return $item->price * $item->quantity;
        });

        $shippingFee = $request->shippingMethod === 'express' ? 300 : ($request->shippingMethod === 'pickup' ? 0 : 150);
        $total = $subtotal + $shippingFee;

        $clientId = app(EcommerceClientContext::class)->clientId();
        if (! $clientId) {
            return response()->json(['success' => false, 'message' => 'Storefront client could not be resolved.'], 422);
        }

        // Build shipping address from saved address record
        $fullName = $address->full_name ?? $user->name;
        $nameParts = explode(' ', $fullName, 2);
        $shippingAddress = [
            'first_name' => $nameParts[0] ?? $fullName,
            'last_name' => $nameParts[1] ?? '',
            'phone' => $address->phone_number ?? '',
            'address' => trim(($address->detailed_address ?? '') . ', ' . ($address->barangay ?? '')),
            'city' => $address->city ?? '',
            'province' => $address->province ?? '',
            'zip' => $address->postal_code ?? '',
            'country' => $address->region ?? 'Philippines',
        ];

        try {
            // 1. Create the order within its own ecommerce transaction
            $order = DB::connection('ecommerce')->transaction(function () use ($cart, $request, $subtotal, $shippingFee, $total, $paymentLabel, $shippingAddress) {
                $order = Order::create([
                    'user_id' => Auth::guard('ecommerce')->id(),
                    'status' => 'processing',
                    'total' => $total,
                    'shipping_fee' => $shippingFee,
                    'payment_method' => $paymentLabel,
                    'payment_status' => $request->paymentMethod === 'cod' ? 'unpaid' : 'paid',
                    'shipping_address' => $shippingAddress,
                    'tracking_number' => 'TF-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]);

                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_type' => $item->product_type,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'configuration' => $item->configuration,
                    ]);
                }

                return $order;
            });

            // 2. Propagate to ERP outside the ecommerce transaction.
            //    Each module handles its own database transaction independently,
            //    avoiding cross-connection transaction aborts like PostgreSQL 25P02.
            app(ErpIntegrationService::class)->propagateEcommerceOrder($clientId, $order, $order->items);

            // 3. Clear Cart only after every required ERP record has been created.
            $cart->items()->delete();
        } catch (\RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'redirect_url' => route('ecommerce.checkout.success', $order->id)
        ]);
    }

    public function success($store, $id)
    {
        $order = Order::with('items')->where('user_id', Auth::guard('ecommerce')->id())->findOrFail($id);
        return view('ecommerce::checkout-success', compact('order'));
    }
}
