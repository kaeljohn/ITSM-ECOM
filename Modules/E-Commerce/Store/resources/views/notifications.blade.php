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
        $store = $store ?? 'techforge';
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
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <title>{{ $storefrontName }} | Notifications</title>
    
    <!-- Tailwind CSS -->
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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
        }

        /* Ambient Radial Light Blurs */
        .ambient-light-1 {
            position: fixed;
            top: -20%;
            left: -20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.35) 0%, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse1 20s ease-in-out infinite;
        }

        .ambient-light-2 {
            position: fixed;
            top: 35%;
            right: -20%;
            width: 80vw;
            height: 80vw;
            background: radial-gradient(circle, rgba({{ $accentR }}, {{ $accentG }}, {{ $accentB }}, 0.4) 0%, rgba({{ $accentR }}, {{ $accentG }}, {{ $accentB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }

        @keyframes floatPulse1 {
            0% {
                opacity: 0.3;
                transform: translate(0, 0) scale(0.8);
            }
            33% {
                opacity: 0.8;
                transform: translate(25vw, 15vh) scale(1.2);
            }
            66% {
                opacity: 0.4;
                transform: translate(-10vw, 30vh) scale(0.9);
            }
            100% {
                opacity: 0.3;
                transform: translate(0, 0) scale(0.8);
            }
        }

        @keyframes floatPulse2 {
            0% {
                opacity: 0.8;
                transform: translate(0, 0) scale(1.1);
            }
            33% {
                opacity: 0.3;
                transform: translate(-25vw, -15vh) scale(0.8);
            }
            66% {
                opacity: 0.7;
                transform: translate(15vw, -25vh) scale(1.3);
            }
            100% {
                opacity: 0.8;
                transform: translate(0, 0) scale(1.1);
            }
        }

        /* Orange Gradient Text */
        .text-gradient {
            background: linear-gradient(to right, #ffffff, #ffaa66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #050505;
        }
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: {{ $primaryHex }};
        }

        /* Preloader Animations */
        @keyframes spinFastOnce {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(720deg); }
        }
        .animate-spin-fast {
            animation: spinFastOnce 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        @keyframes slideTextOut {
            0% { max-width: 0; opacity: 0; padding-left: 0; }
            100% { max-width: 400px; opacity: 1; padding-left: 1.5rem; }
        }
        .animate-slide-text {
            animation: slideTextOut 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            animation-delay: 0.8s;
            overflow: hidden;
            white-space: nowrap;
            opacity: 0;
            max-width: 0;
        }
    </style>

    @vite('Modules/E-Commerce/Store/resources/css/liquidglass.css')
</head>
<body class="relative antialiased selection:bg-primary selection:text-white">

    <!-- Preloader -->
    <div id="preloader" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
        <script>
            if (!sessionStorage.getItem('{{ $store }}_visited')) {
                document.write(`
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full animate-pulse"></div>
                        <div class="flex items-center relative z-10">
                            <img src="{{ $logoUrl }}" alt="{{ $storefrontName }} Logo" class="h-20 w-auto object-contain animate-spin-fast drop-shadow-[0_0_25px_rgba(255,107,0,0.6)]">
                            <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-slide-text">{{ strtoupper($storefrontName) }}</span>
                        </div>
                    </div>
                `);
            } else {
                document.write(`
                    <div class="w-16 h-16 border-4 border-white/10 border-t-primary rounded-full animate-spin shadow-[0_0_20px_rgba(255,107,0,0.3)]"></div>
                `);
            }
        </script>
    </div>

    <!-- Background Ambient Effects -->
    <div class="ambient-light-1"></div>
    <div class="ambient-light-2"></div>



    <x-navbar />

    <!-- Notifications Section -->
    <main class="relative pt-40 pb-20 lg:pt-48 lg:pb-28 overflow-hidden z-10 min-h-screen">
        <div class="max-w-4xl mx-auto px-10 sm:px-12 lg:px-14">
            
            <!-- Page Header -->
            <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-6">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mb-2">Notifications</h1>
                    <p class="text-sm text-gray-400">
                        @auth('ecommerce')
                            @if($unreadCount > 0)
                                You have <strong class="text-primary">{{ $unreadCount }}</strong> unread notification{{ $unreadCount !== 1 ? 's' : '' }}.
                            @else
                                All caught up!
                            @endif
                        @endauth
                        @guest('ecommerce')
                            <a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="text-primary hover:underline">Sign in</a> to see your notifications.
                        @endguest
                    </p>
                </div>
                @auth('ecommerce')
                    @if($unreadCount > 0)
                        <button id="customer-notif-mark-all" class="text-sm font-bold text-gray-400 hover:text-white transition-colors flex items-center gap-2">
                            <i class="ph-bold ph-check-circle"></i> Mark all as read
                        </button>
                    @endif
                @endauth
            </div>

            <div class="space-y-4" id="customer-notif-list">
                @auth('ecommerce')
                    @forelse($notifications as $notif)
                        <a href="{{ $notif->link ?: '#' }}" class="liquid-glass rounded-3xl p-6 border border-white/10 flex flex-col sm:flex-row items-start gap-6 relative group hover:border-primary/30 transition-all shadow-[0_5px_20px_rgba(0,0,0,0.5)] bg-white/5 {{ $notif->is_read ? '' : 'border-primary/20' }}">
                            <div class="w-14 h-14 rounded-full bg-{{ $notif->icon_color ?? 'primary' }}/20 flex items-center justify-center shrink-0 border border-{{ $notif->icon_color ?? 'primary' }}/20">
                                <i class="ph-fill {{ $notif->icon ?? 'ph-megaphone' }} text-2xl text-{{ $notif->icon_color ?? 'primary' }}"></i>
                            </div>
                            <div class="flex-1 flex flex-col justify-center py-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-lg font-bold text-white group-hover:text-primary transition-colors">{{ $notif->title }}</h3>
                                    @if(!$notif->is_read)
                                        <span class="w-2 h-2 rounded-full bg-primary shadow-[0_0_8px_rgba(255,107,0,0.8)]"></span>
                                    @endif
                                </div>
                                @if($notif->body)
                                    <p class="text-sm text-gray-300 leading-relaxed mb-3">{{ $notif->body }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-auto">
                                    <span class="text-xs text-gray-500 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                    @if($notif->link)
                                        <span class="text-xs font-bold text-primary hover:text-white transition-colors flex items-center gap-1 group/link">
                                            View <i class="ph-bold ph-arrow-right group-hover/link:translate-x-1 transition-transform"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-20">
                            <i class="ph ph-bell-slash text-5xl text-gray-600 mb-4 block"></i>
                            <h3 class="text-xl font-bold text-white mb-2">No notifications yet</h3>
                            <p class="text-gray-400 text-sm">Notifications from the store will appear here.</p>
                        </div>
                    @endforelse

                    @if($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="pt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-20">
                        <i class="ph ph-bell-slash text-5xl text-gray-600 mb-4 block"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Sign in to see notifications</h3>
                        <p class="text-gray-400 text-sm mb-6">Log in to receive order updates, promotions, and more.</p>
                        <a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="inline-block bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-xl font-bold transition-colors shadow-[0_0_20px_rgba(255,107,0,0.3)]">
                            Sign In
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </main>

    @auth('ecommerce')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var markAllBtn = document.getElementById('customer-notif-mark-all');
        if (!markAllBtn) return;

        markAllBtn.addEventListener('click', function() {
            markAllBtn.disabled = true;
            markAllBtn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Marking...';

            fetch('{{ route("ecommerce.api.notifications.mark-all-read", ["store" => $store]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success) {
                    // Remove unread indicators
                    document.querySelectorAll('#customer-notif-list a').forEach(function(el) {
                        el.classList.remove('border-primary/20');
                        var dot = el.querySelector('.w-2\.h-2\.rounded-full');
                        if (dot) dot.remove();
                    });
                    markAllBtn.innerHTML = '<i class="ph-bold ph-check-circle"></i> All marked as read';
                    markAllBtn.disabled = true;
                }
            })
            .catch(function() {
                markAllBtn.disabled = false;
                markAllBtn.innerHTML = '<i class="ph-bold ph-check-circle"></i> Mark all as read';
            });
        });
    });
    </script>
    @endauth

    <x-footer />


    

    @vite(['Modules/E-Commerce/Store/resources/js/Common/Preloader.js', 'Modules/E-Commerce/Store/resources/js/Common/AmbientEffects.js'])

    <!-- Load our compiled JavaScript -->
    @vite('Modules/E-Commerce/Store/resources/js/HomePage/Homepage.js')
</body>
</html>
