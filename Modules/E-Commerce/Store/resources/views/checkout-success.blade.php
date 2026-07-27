@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    if ($storefrontCompany) {
        $isPreview = request()->boolean('preview') && \Illuminate\Support\Facades\Auth::guard('ecommerce_admin')->check();
        $publishedLayout = $isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($storefrontCompany) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($storefrontCompany);
        $layout = empty($layout) ? $publishedLayout : $layout;
        $storefrontName = $storefrontName ?? ($publishedLayout['brand_name'] ?? ($storefrontCompany->company_name ?: 'Nexora Store'));
        $store = $store ?? $storefrontCompany->ecommerce_slug;
        $logoUrl = $logoUrl ?? (!empty($publishedLayout['logo_path']) ? (str_starts_with($publishedLayout['logo_path'], 'Modules/') ? Vite::asset($publishedLayout['logo_path']) : asset('storage/'.$publishedLayout['logo_path'])) : ($storefrontCompany->logoUrl() ?: asset('ecommerce/Nexora_Logo.png')));
    } else {
        $storefrontName = $storefrontName ?? 'Nexora Store';
        $store = $store ?? 'store';
        $logoUrl = $logoUrl ?? asset('ecommerce/Nexora_Logo.png');
        $layout = [];
    }

    $primaryHex = $layout['primary_color'] ?? '#ff6b00';
    $primaryClean = ltrim($primaryHex, '#');
    if (strlen($primaryClean) === 3) $primaryClean = $primaryClean[0].$primaryClean[0].$primaryClean[1].$primaryClean[1].$primaryClean[2].$primaryClean[2];
    $primaryR = hexdec(substr($primaryClean, 0, 2));
    $primaryG = hexdec(substr($primaryClean, 2, 2));
    $primaryB = hexdec(substr($primaryClean, 4, 2));

    $accentHex = $layout['accent_color'] ?? '#f59e0b';
    $accentClean = ltrim($accentHex, '#');
    if (strlen($accentClean) === 3) $accentClean = $accentClean[0].$accentClean[0].$accentClean[1].$accentClean[1].$accentClean[2].$accentClean[2];
    $accentR = hexdec(substr($accentClean, 0, 2));
    $accentG = hexdec(substr($accentClean, 2, 2));
    $accentB = hexdec(substr($accentClean, 4, 2));

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
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'ph-gear', 'time' => 'In progress'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'ph-truck', 'time' => 'Pending'],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'ph-package', 'time' => 'Pending'],
    ];
    $statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
    $currentStatusIndex = $statusOrder[$orderStatus] ?? 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>{{ $storefrontName }} | Order Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '{{ $primaryHex }}', hover: '{{ $primaryHex }}CC', glow: '{{ $primaryHex }}80' },
                        accent: '{{ $accentHex }}',
                        dark: { bg: '#050505', surface: '#121212' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    dropShadow: {
                        'glow': '0 0 15px {{ $primaryHex }}80',
                    },
                    boxShadow: {
                        'glow': '0 0 20px {{ $primaryHex }}4D',
                        'glow-lg': '0 0 30px {{ $primaryHex }}26',
                    }
                }
            }
        };
    </script>
    
    <!-- Confetti library -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>

    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, .tech-font {
            font-family: 'Chakra Petch', sans-serif;
        }
        .code-font {
            font-family: 'JetBrains Mono', monospace;
        }
        .ambient-light {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80vw;
            height: 80vw;
            background: radial-gradient(circle, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.15) 0%, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
        }

        /* ── Timeline Styles ── */
        .timeline-line {
            position: absolute;
            left: 19px;
            top: 40px;
            bottom: 40px;
            width: 2px;
            background: linear-gradient(to bottom, {{ $primaryHex }}, rgba(255,255,255,0.08));
        }
        .timeline-step {
            position: relative;
            padding-left: 52px;
            padding-bottom: 28px;
        }
        .timeline-step:last-child {
            padding-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: 10px;
            top: 2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            z-index: 2;
            transition: all 0.5s ease;
        }
        .timeline-dot.completed {
            background-color: #22c55e;
            color: #000;
            box-shadow: 0 0 12px rgba(34,197,94,0.5);
        }
        .timeline-dot.active {
            background-color: {{ $primaryHex }};
            color: #000;
            box-shadow: 0 0 16px {{ $primaryHex }}80;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        .timeline-dot.pending {
            background-color: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 {{ $primaryHex }}80; transform: scale(1); }
            50% { box-shadow: 0 0 0 8px {{ $primaryHex }}30, 0 0 0 16px {{ $primaryHex }}10; transform: scale(1.1); }
        }
        .timeline-label {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }
        .timeline-label.completed { color: #22c55e; }
        .timeline-label.active { color: #fff; }
        .timeline-label.pending { color: rgba(255,255,255,0.3); }
        .timeline-time {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            font-family: 'JetBrains Mono', monospace;
            margin-top: 2px;
        }

        /* Countdown badge */
        .countdown-number {
            font-size: 2.5rem;
            font-weight: 900;
            font-family: 'Chakra Petch', sans-serif;
        }

        /* Entrance animation for the success card */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-entrance {
            animation: fadeSlideUp 0.6s ease-out forwards;
        }
        .animate-entrance-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-entrance-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-entrance-delay-3 { animation-delay: 0.35s; opacity: 0; }
        .animate-entrance-delay-4 { animation-delay: 0.5s; opacity: 0; }
    </style>
</head>
<body class="relative antialiased selection:bg-primary selection:text-white min-h-screen flex flex-col items-center justify-center py-12">

    <div class="ambient-light"></div>

    <div class="container mx-auto px-4 max-w-2xl relative z-10 text-center">
        
        <!-- Success Icon (entrance animated) -->
        <div class="animate-entrance animate-entrance-delay-1 w-32 h-32 bg-green-500/10 rounded-full border border-green-500/30 flex items-center justify-center mx-auto mb-8 relative">
            <div class="absolute inset-0 bg-green-500/20 rounded-full animate-ping"></div>
            <i class="ph-bold ph-check text-6xl text-green-500 relative z-10"></i>
        </div>

        <h1 class="animate-entrance animate-entrance-delay-1 text-4xl md:text-5xl font-black text-white mb-4 tracking-tight tech-font">Order Confirmed!</h1>
        <p class="animate-entrance animate-entrance-delay-2 text-gray-400 text-lg mb-8">Thank you for your purchase. We've received your order and are currently processing it.</p>

        <!-- Estimated Delivery Countdown -->
        <div class="animate-entrance animate-entrance-delay-2 bg-black/40 border border-white/10 rounded-2xl p-5 mb-6 text-left shadow-lg backdrop-blur-md flex items-center justify-between gap-4">
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

        <!-- Order Details Card -->
        <div class="animate-entrance animate-entrance-delay-3 bg-black/40 border border-white/10 rounded-3xl p-6 md:p-8 mb-6 text-left shadow-2xl backdrop-blur-md">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-white/10 pb-6 mb-6">
                <div>
                    <p class="text-sm text-gray-500 font-bold uppercase tracking-widest mb-1 tech-font">Order Number</p>
                    <p class="text-xl font-black text-white code-font">{{ $order->tracking_number }}</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:text-right">
                    <p class="text-sm text-gray-500 font-bold uppercase tracking-widest mb-1 tech-font">Date</p>
                    <p class="text-white font-medium text-sm">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="space-y-4 mb-6 max-h-[30vh] overflow-y-auto pr-2">
                @foreach($order->items as $item)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-white/5 flex items-center justify-center text-xs font-bold text-gray-400 code-font">
                            {{ $item->quantity }}x
                        </div>
                        <p class="text-sm text-white font-medium line-clamp-1">{{ $item->name }}</p>
                    </div>
                    <p class="text-sm text-white font-bold shrink-0 code-font">₱{{ number_format($item->price * $item->quantity, 2) }}</p>
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
                    <span class="text-base text-white font-bold">Total Paid</span>
                    <span class="text-2xl font-black text-primary code-font">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="animate-entrance animate-entrance-delay-3 bg-black/40 border border-white/10 rounded-3xl p-6 md:p-8 mb-6 text-left shadow-2xl backdrop-blur-md">
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
                        if ($index < $currentStatusIndex) {
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
                        <div class="timeline-time">{{ $step['time'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="animate-entrance animate-entrance-delay-4 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/') }}" class="bg-primary hover:bg-white hover:text-black text-white px-8 py-4 rounded-xl font-black uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,255,255,0.5)] tech-font text-sm">
                Continue Shopping
            </a>
            <a href="{{ route('ecommerce.account.order-history', ['store' => $store]) }}" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-8 py-4 rounded-xl font-bold transition-all text-sm">
                View Orders
            </a>
        </div>
    </div>

    <script>
        // ── Confetti Celebration ──
        (function() {
            var primaryR = {{ $primaryR }};
            var primaryG = {{ $primaryG }};
            var primaryB = {{ $primaryB }};
            var accentR = {{ $accentR }};
            var accentG = {{ $accentG }};
            var accentB = {{ $accentB }};

            // First burst: big celebration
            setTimeout(function() {
                canvasConfetti({
                    particleCount: 150,
                    spread: 80,
                    origin: { y: 0.55 },
                    colors: [
                        'rgb(' + primaryR + ',' + primaryG + ',' + primaryB + ')',
                        'rgb(' + accentR + ',' + accentG + ',' + accentB + ')',
                        '#22c55e',
                        '#ffffff'
                    ],                        shapes: ['square', 'circle'],
                        ticks: 250,
                        gravity: 0.7,
                        scalar: 1.2
                });
            }, 300);

            // Second burst: from the left
            setTimeout(function() {
                canvasConfetti({
                    particleCount: 80,
                    spread: 60,
                    angle: 60,
                    origin: { x: 0, y: 0.6 },
                    colors: [
                        'rgb(' + primaryR + ',' + primaryG + ',' + primaryB + ')',
                        'rgb(' + accentR + ',' + accentG + ',' + accentB + ')'
                    ],
                    shapes: ['circle'],
                    ticks: 150
                });
            }, 500);

            // Third burst: from the right
            setTimeout(function() {
                canvasConfetti({
                    particleCount: 80,
                    spread: 60,
                    angle: 120,
                    origin: { x: 1, y: 0.6 },
                    colors: [
                        'rgb(' + primaryR + ',' + primaryG + ',' + primaryB + ')',
                        '#22c55e'
                    ],
                    shapes: ['circle'],
                    ticks: 150
                });
            }, 700);

            // Fourth burst: gentle rain
            setTimeout(function() {
                canvasConfetti({
                    particleCount: 50,
                    spread: 120,
                    origin: { y: 0.3 },
                    colors: ['#ffffff', 'rgb(' + primaryR + ',' + primaryG + ',' + primaryB + ')'],                        shapes: ['circle'],
                        ticks: 200,
                        gravity: 0.5,
                        scalar: 0.8
                });
            }, 1000);
        })();
    </script>

</body>
</html>