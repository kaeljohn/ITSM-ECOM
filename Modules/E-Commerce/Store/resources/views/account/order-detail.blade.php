@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    if ($storefrontCompany) {
        $isPreview = request()->boolean('preview') && \Illuminate\Support\Facades\Auth::guard('ecommerce_admin')->check();
        $publishedLayout = $isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($storefrontCompany) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($storefrontCompany);
        $layout = empty($layout) ? ($publishedLayout ?? []) : $layout;
        $storefrontName = $storefrontName ?? ($publishedLayout['brand_name'] ?? ($storefrontCompany->company_name ?: 'Nexora Store'));
        $store = $store ?? $storefrontCompany->ecommerce_slug;
        $logoUrl = $logoUrl ?? (!empty($publishedLayout['logo_path']) ? (str_starts_with($publishedLayout['logo_path'], 'Modules/') ? Vite::asset($publishedLayout['logo_path']) : asset('storage/'.$publishedLayout['logo_path'])) : ($storefrontCompany->logoUrl() ?: asset('ecommerce/Nexora_Logo.png')));
    } else {
        $storefrontName = $storefrontName ?? 'Nexora Store';
        $store = $store ?? request()->route('store') ?? 'techforge';
        $logoUrl = $logoUrl ?? asset('ecommerce/Nexora_Logo.png');
        $layout = [];
    }

    $primaryHex = $layout['primary_color'] ?? '#ff6b00';
    $primaryClean = ltrim($primaryHex, '#');
    if (strlen($primaryClean) === 3) $primaryClean = $primaryClean[0].$primaryClean[0].$primaryClean[1].$primaryClean[1].$primaryClean[2].$primaryClean[2];
    $primaryR = hexdec(substr($primaryClean, 0, 2));
    $primaryG = hexdec(substr($primaryClean, 2, 2));
    $primaryB = hexdec(substr($primaryClean, 4, 2));

    // ── Estimate delivery date ──
    $shippingMethod = ($order->shipping_fee ?? 0) >= 300 ? 'express' : 'standard';
    $businessDays = $shippingMethod === 'express' ? 2 : 5;
    $estimatedDate = \Carbon\Carbon::parse($order->created_at)->addWeekdays($businessDays);
    $estimatedFormatted = $estimatedDate->format('l, F jS');
    $daysUntilDelivery = now()->diffInDays($estimatedDate, false);
    $daysUntilDelivery = max(0, (int) ceil($daysUntilDelivery));

    // ── Order status for timeline ──
    $orderStatus = $order->status ?? 'pending';
    $timelineSteps = [
        ['key' => 'received', 'label' => 'Order Received', 'icon' => 'ph-check-circle', 'time' => $order->created_at->format('M d, h:i A')],
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'ph-gear', 'time' => $orderStatus === 'processing' ? 'In progress' : 'Complete'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'ph-truck', 'time' => $orderStatus === 'shipped' ? 'In transit' : ($orderStatus === 'delivered' ? 'Delivered' : 'Pending')],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'ph-package', 'time' => $orderStatus === 'delivered' ? $order->updated_at->format('M d, h:i A') : 'Pending'],
    ];
    $statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3, 'cancelled' => 0];
    $currentStatusIndex = $statusOrder[$orderStatus] ?? 0;

    // ── Status badge ──
    $statusLabels = [
        'pending' => ['label' => 'Pending', 'color' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'],
        'processing' => ['label' => 'Processing', 'color' => 'bg-blue-500/20 text-blue-400 border-blue-500/30'],
        'shipped' => ['label' => 'Shipped', 'color' => 'bg-primary/20 text-primary border-primary/30'],
        'delivered' => ['label' => 'Delivered', 'color' => 'bg-green-500/20 text-green-400 border-green-500/30'],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-500/20 text-red-400 border-red-500/30'],
    ];
    $statusBadge = $statusLabels[$orderStatus] ?? $statusLabels['pending'];

    // ── Parse shipping address ──
    $shippingAddr = $order->shipping_address_parsed ?? $order->shipping_address;
    if (is_string($shippingAddr)) {
        $shippingAddr = json_decode($shippingAddr, true) ?: [];
    }
    $addrLines = [];
    if (!empty($shippingAddr['address'])) $addrLines[] = $shippingAddr['address'];
    if (!empty($shippingAddr['city'])) $addrLines[] = $shippingAddr['city'];
    if (!empty($shippingAddr['province'])) $addrLines[] = $shippingAddr['province'];
    if (!empty($shippingAddr['zip'])) $addrLines[] = $shippingAddr['zip'];
    if (!empty($shippingAddr['raw'])) $addrLines = [$shippingAddr['raw']];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>{{ $storefrontName }} | Order #{{ $order->tracking_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '{{ $primaryHex }}', hover: '{{ $primaryHex }}CC', glow: '{{ $primaryHex }}80' },
                        dark: { bg: '#050505', surface: '#121212' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    boxShadow: { 'glow': '0 0 20px {{ $primaryHex }}4D' }
                }
            }
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #050505; color: #ffffff; overflow-x: hidden; }
        h1, h2, h3, h4, .tech-font { font-family: 'Chakra Petch', sans-serif; }
        .code-font { font-family: 'JetBrains Mono', monospace; }
        .ambient-light {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 80vw; height: 80vw;
            background: radial-gradient(circle, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.12) 0%, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0) 65%);
            z-index: -1; pointer-events: none;
        }
        /* Timeline */
        .timeline-line { position: absolute; left: 19px; top: 40px; bottom: 40px; width: 2px; background: linear-gradient(to bottom, {{ $primaryHex }}, rgba(255,255,255,0.08)); }
        .timeline-step { position: relative; padding-left: 52px; padding-bottom: 28px; }
        .timeline-step:last-child { padding-bottom: 0; }
        .timeline-dot {
            position: absolute; left: 10px; top: 2px; width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 10px; z-index: 2;
            transition: all 0.5s ease;
        }
        .timeline-dot.completed { background-color: #22c55e; color: #000; box-shadow: 0 0 12px rgba(34,197,94,0.5); }
        .timeline-dot.active {
            background-color: {{ $primaryHex }}; color: #000;
            box-shadow: 0 0 16px {{ $primaryHex }}80;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        .timeline-dot.pending { background-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.15); }
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 {{ $primaryHex }}80; transform: scale(1); }
            50% { box-shadow: 0 0 0 8px {{ $primaryHex }}30, 0 0 0 16px {{ $primaryHex }}10; transform: scale(1.1); }
        }
        .timeline-label { font-size: 14px; font-weight: 600; color: #fff; }
        .timeline-label.completed { color: #22c55e; }
        .timeline-label.active { color: #fff; }
        .timeline-label.pending { color: rgba(255,255,255,0.3); }
        .timeline-time { font-size: 11px; color: rgba(255,255,255,0.35); font-family: 'JetBrains Mono', monospace; margin-top: 2px; }
        .countdown-number { font-size: 2.5rem; font-weight: 900; font-family: 'Chakra Petch', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #05050A; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    </style>
</head>
<body class="relative antialiased min-h-screen py-8">
    <div class="ambient-light"></div>

    <div class="container mx-auto px-4 max-w-3xl relative z-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                <img src="{{ $logoUrl }}" alt="Logo" class="h-8 w-auto group-hover:opacity-90 transition-opacity">
                <span class="text-lg font-bold text-white tech-font group-hover:text-primary transition-colors">{{ strtoupper($storefrontName) }}</span>
            </a>
            <a href="{{ route('ecommerce.account.order-history', ['store' => $store]) }}" class="flex items-center gap-2 text-sm text-gray-400 hover:text-primary transition-colors">
                <i class="ph ph-arrow-left"></i>
                <span>All Orders</span>
            </a>
        </div>

        <!-- Order Header Card -->
        <div class="bg-black/40 border border-white/10 rounded-2xl p-6 mb-6 shadow-lg backdrop-blur-md">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-1 tech-font">Order Number</p>
                    <p class="text-xl font-black text-white code-font">{{ $order->tracking_number }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-1 tech-font">Date</p>
                        <p class="text-white text-sm">{{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                    <span class="px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-widest border {{ $statusBadge['color'] }}">
                        {{ $statusBadge['label'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Estimated Delivery Countdown -->
        @if($orderStatus !== 'delivered' && $orderStatus !== 'cancelled')
        <div class="bg-black/40 border border-white/10 rounded-2xl p-5 mb-6 shadow-lg backdrop-blur-md flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center shrink-0">
                    <i class="ph ph-rocket-launch text-primary text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-0.5">Estimated Delivery</p>
                    <p class="text-sm font-bold text-white">{{ $estimatedFormatted }}</p>
                </div>
            </div>
            <div class="text-right shrink-0">
                <span class="text-xs text-gray-500 uppercase tracking-widest block">In</span>
                <span class="countdown-number text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-400">{{ $daysUntilDelivery }}</span>
                <span class="text-xs text-gray-500 uppercase tracking-widest">{{ $daysUntilDelivery === 1 ? 'Day' : 'Days' }}</span>
            </div>
        </div>
        @endif

        <!-- Order Timeline Card -->
        <div class="bg-black/40 border border-white/10 rounded-2xl p-6 md:p-8 mb-6 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/30 flex items-center justify-center">
                    <i class="ph ph-truck text-primary"></i>
                </div>
                <h3 class="text-base font-bold text-white tech-font tracking-wider uppercase">Order Progress</h3>
            </div>
            <div class="relative">
                <div class="timeline-line"></div>
                @foreach($timelineSteps as $index => $step)
                    @php
                        $isCancelled = $orderStatus === 'cancelled';
                        if ($isCancelled) {
                            $dotClass = $index === 0 ? 'completed' : 'pending';
                            $labelClass = $dotClass === 'completed' ? 'completed' : 'pending';
                            $icon = $index === 0 ? 'ph-bold ph-check' : 'ph ' . $step['icon'];
                        } elseif ($index < $currentStatusIndex) {
                            $dotClass = 'completed';
                            $labelClass = 'completed';
                            $icon = 'ph-bold ph-check';
                        } elseif ($index === $currentStatusIndex) {
                            $dotClass = 'active';
                            $labelClass = 'active';
                            $icon = 'ph-bold ' . $step['icon'];
                        } else {
                            $dotClass = 'pending';
                            $labelClass = 'pending';
                            $icon = 'ph ' . $step['icon'];
                        }
                    @endphp
                    <div class="timeline-step">
                        <div class="timeline-dot {{ $dotClass }}">
                            <i class="{{ $icon }}" style="font-size: 11px;"></i>
                        </div>
                        <div class="timeline-label {{ $labelClass }}">{{ $step['label'] }}</div>
                        <div class="timeline-time">{{ $isCancelled && $index > 0 ? '—' : $step['time'] }}</div>
                    </div>
                @endforeach
            </div>
            @if($orderStatus === 'cancelled')
                <div class="mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center gap-3">
                    <i class="ph ph-warning-circle text-red-400 text-lg"></i>
                    <p class="text-sm text-red-300">This order has been cancelled.</p>
                </div>
            @endif
        </div>

        <!-- Items Card -->
        <div class="bg-black/40 border border-white/10 rounded-2xl p-6 md:p-8 mb-6 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                    <i class="ph ph-package text-gray-400"></i>
                </div>
                <h3 class="text-base font-bold text-white tech-font tracking-wider uppercase">Order Items</h3>
            </div>
            <div class="space-y-4 mb-6">
                @foreach($order->items as $item)
                <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-xs font-bold text-gray-400 code-font">
                            {{ $item->quantity }}x
                        </div>
                        <div>
                            <p class="text-sm text-white font-medium">{{ $item->name }}</p>
                            @if(!empty($item->product_type))
                                <p class="text-[10px] text-gray-500 code-font">{{ $item->product_type }}</p>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-white font-bold code-font shrink-0">₱{{ number_format($item->price * $item->quantity, 2) }}</p>
                </div>
                @endforeach
            </div>

            <div class="border-t border-white/10 pt-4 space-y-2">
                <div class="flex justify-between text-sm text-gray-400">
                    <span>Subtotal</span>
                    <span class="code-font">₱{{ number_format($order->total - $order->shipping_fee, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-400">
                    <span>Shipping ({{ $shippingMethod === 'express' ? 'Express' : 'Standard' }})</span>
                    <span class="code-font">₱{{ number_format($order->shipping_fee, 2) }}</span>
                </div>
                <div class="flex justify-between items-end mt-4 pt-4 border-t border-white/5">
                    <span class="text-base text-white font-bold">Total</span>
                    <span class="text-2xl font-black text-primary code-font">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Shipping Address Card -->
        <div class="bg-black/40 border border-white/10 rounded-2xl p-6 md:p-8 mb-6 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                    <i class="ph ph-map-pin text-gray-400"></i>
                </div>
                <h3 class="text-base font-bold text-white tech-font tracking-wider uppercase">Shipping Address</h3>
            </div>
            @if(!empty($addrLines))
                <p class="text-sm text-gray-300 code-font leading-relaxed">
                    @if(!empty($shippingAddr['first_name']))
                        {{ $shippingAddr['first_name'] }} {{ $shippingAddr['last_name'] ?? '' }}<br>
                    @elseif(!empty($shippingAddr['name']))
                        {{ $shippingAddr['name'] }}<br>
                    @endif
                    @foreach($addrLines as $line)
                        {{ $line }}<br>
                    @endforeach
                    @if(!empty($shippingAddr['country']))
                        {{ $shippingAddr['country'] }}
                    @endif
                </p>
            @else
                <p class="text-sm text-gray-500">Shipping address details not available.</p>
            @endif

            @if(!empty($shippingAddr['phone']))
                <div class="mt-3 flex items-center gap-2 text-sm text-gray-400">
                    <i class="ph ph-phone text-gray-500"></i>
                    <span class="code-font">{{ $shippingAddr['phone'] }}</span>
                </div>
            @endif
        </div>

        <!-- Payment Info Card -->
        <div class="bg-black/40 border border-white/10 rounded-2xl p-6 md:p-8 mb-8 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                    <i class="ph ph-credit-card text-gray-400"></i>
                </div>
                <h3 class="text-base font-bold text-white tech-font tracking-wider uppercase">Payment</h3>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-300">{{ $order->payment_method ?? 'Cash on Delivery' }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-widest
                    {{ ($order->payment_status ?? 'unpaid') === 'paid' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                    {{ ($order->payment_status ?? 'unpaid') === 'paid' ? 'Paid' : 'Unpaid' }}
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('ecommerce.account.order-history', ['store' => $store]) }}" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-6 py-3 rounded-xl font-bold transition-all text-sm text-center">
                <i class="ph ph-list"></i> All Orders
            </a>
            <a href="{{ url('/') }}" class="bg-primary hover:bg-white hover:text-black text-white px-6 py-3 rounded-xl font-bold uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] text-sm text-center">
                Continue Shopping
            </a>
        </div>
    </div>
</body>
</html>