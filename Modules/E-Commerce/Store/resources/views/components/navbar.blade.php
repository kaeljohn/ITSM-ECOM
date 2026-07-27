@props(['storefrontName' => null, 'store' => null, 'logoUrl' => null, 'layout' => []])
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
        // Fallback for preview mode or when middleware doesn't set it
        // We will pass these as props from storefront.blade.php
        $storefrontName = $storefrontName ?? 'Nexora Store';
        $store = $store ?? 'store';
        $logoUrl = $logoUrl ?? asset('ecommerce/Nexora_Logo.png');
    }

    $navbar = $layout['navbar'] ?? [];
    $announcement_enabled = $navbar['announcement_enabled'] ?? false;
    $announcement_text = $navbar['announcement_text'] ?? 'Ã°Å¸â€Â¥ Free shipping on all orders over Ã¢â€šÂ±50,000!';
    $announcement_url = $navbar['announcement_url'] ?? '';
    $search_placeholder = $navbar['search_placeholder'] ?? 'What are we searching?';
    // Fetch active store listings for the search dropdown
    $suggestedListings = \Modules\Ecommerce\Models\StorefrontListing::where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    $mega_pc_title = $navbar['mega_pc_title'] ?? 'PC FORGE';
    $mega_pc_subtitle = $navbar['mega_pc_subtitle'] ?? 'Use our exclusive PC Forge tool to build your ultimate rig entirely from scratch, part by part.';
    $mega_pc_button = $navbar['mega_pc_button'] ?? 'Launch PC Forge';
    $mega_pc_url = $navbar['mega_pc_url'] ?? '#pc-forge';
    $mega_laptop_title = $navbar['mega_laptop_title'] ?? 'POWER ON THE GO';
    $mega_laptop_subtitle = $navbar['mega_laptop_subtitle'] ?? 'Experience desktop-class performance wherever you are with our RTX 40-series gaming laptops.';
    $mega_laptop_button = $navbar['mega_laptop_button'] ?? 'Shop Laptops';
    $mega_laptop_url = $navbar['mega_laptop_url'] ?? '#laptops';
    $nav_pc_forge_enabled = $navbar['nav_pc_forge_enabled'] ?? true;
    $links = !empty($navbar['links']) ? $navbar['links'] : [
        ['label' => 'Category Showcase 1', 'url' => '/categories/category1'],
        ['label' => 'Category Showcase 2', 'url' => '/categories/category2'],
        ['label' => 'Category Showcase 3', 'url' => '/categories/category3']
    ];
@endphp

    <!-- Announcement Bar -->
    <div id="announcement-bar" class="fixed top-0 left-0 w-full z-[85] liquid-glass backdrop-blur-xl bg-gradient-to-r from-primary/30 via-primary/20 to-accent/30 border-b border-white/10 text-white text-xs font-bold text-center py-2.5 shadow-lg flex justify-center items-center gap-2 transition-all {{ $announcement_enabled ? '' : 'hidden' }}">
        <a href="{{ $announcement_url ?: '#' }}" id="announcement-link" class="hover:underline flex items-center gap-2 {{ $announcement_url ? '' : 'pointer-events-none' }}">
            <span id="announcement-text-el">{{ $announcement_text }}</span>
            <i id="announcement-arrow" class="ph-bold ph-arrow-right {{ $announcement_url ? '' : 'hidden' }}"></i>
        </a>
    </div>

    <!-- Search Overlay -->
    <div id="search-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[75] opacity-0 pointer-events-none transition-all duration-300"></div>

    <!-- Nav Overlay (for Peripherals Store dropdown) -->
    <div id="nav-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[65] opacity-0 pointer-events-none transition-all duration-300"></div>

    <!-- Navigation -->
    <nav id="main-nav" class="fixed w-[calc(100%-2rem)] sm:w-[calc(100%-3rem)] lg:w-[calc(100%-4rem)] max-w-7xl left-1/2 -translate-x-1/2 {{ $announcement_enabled ? 'top-10' : 'top-4' }} z-[80] px-4 sm:px-6 py-3 flex items-center justify-between gap-4 sm:gap-6 transition-all duration-300">
        <!-- Background for Nav to prevent backdrop-filter nesting bug -->
        <div class="absolute inset-0 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl pointer-events-none shadow-2xl"></div>

        <!-- Logo & Name -->
        <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 relative z-30">
            <img src="{{ $logoUrl }}" alt="{{ $storefrontName }} logo" class="h-9 w-auto block">
            <span class="hidden md:block text-xl font-bold tracking-wide text-white">{{ $storefrontName }}</span>
        </a>



        <!-- Search Bar (Automatically Enlarged) -->
        <form id="search-container" action="{{ route('ecommerce.search', ['store' => $store]) }}" method="GET" class="flex-1 w-full relative z-50">
            <div id="search-wrapper" class="relative flex items-center w-full h-11 bg-neutral-900 border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all duration-300 rounded-2xl group">
                <input type="text" name="q" id="search-input" placeholder="{{ $search_placeholder }}" class="w-full h-full bg-transparent outline-none pl-5 pr-20 text-sm text-white placeholder-gray-400 font-light rounded-2xl relative z-10" autocomplete="off" value="{{ request('q') }}">

                <!-- Clear Button -->
                <button type="button" id="search-clear" class="absolute right-12 w-7 h-7 flex items-center justify-center text-gray-400 hover:text-white transition-all opacity-0 pointer-events-none z-20">
                    <i class="ph ph-x text-sm"></i>
                </button>

                <button type="submit" class="absolute right-1 w-9 h-9 flex items-center justify-center bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors shadow-glow-sm z-20">
                    <i class="ph ph-magnifying-glass text-lg"></i>
                </button>
            </div>

            <!-- Search Dropdown -->
            <div id="search-dropdown" class="bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 absolute top-[calc(100%+0.5rem)] left-0 w-full rounded-2xl overflow-hidden shadow-2xl py-4 opacity-0 pointer-events-none transition-all duration-300 transform -translate-y-2 origin-top">
                <div class="px-5 mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Suggested Products</span>
                </div>
                <ul class="text-sm text-gray-300 flex flex-col">
                    @forelse($suggestedListings as $listing)
                    <li>
                        <a href="{{ route('ecommerce.listings.show', ['store' => $store, 'listing' => $listing->id]) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors">
                            @if($listing->image_url)
                                <img src="{{ asset('storage/' . $listing->image_url) }}" alt="" loading="lazy" class="lazy-img w-7 h-7 rounded object-cover">
                            @else
                                <i class="ph ph-package text-gray-500"></i>
                            @endif
                            <span class="flex-1 truncate">{{ $listing->name }}</span>
                            <span class="text-xs text-gray-500 font-bold">P{{ number_format($listing->price, 0) }}</span>
                        </a>
                    </li>
                    @empty
                    <li class="px-5 py-3 text-gray-500 text-sm">No listings available yet.</li>
                    @endforelse
                </ul>
            </div>
        </form>

        <!-- Actions -->
        <div class="flex items-center gap-4 shrink-0">

            <!-- Sign In -->
            @auth('ecommerce')
            <div class="hidden lg:flex items-center gap-4 relative group/user py-2">
                <div class="flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-user text-xl text-primary transition-colors"></i>
                    <div class="flex flex-col text-left">
                        <span class="text-[10px] text-gray-400 leading-tight">Welcome</span>
                        <span class="text-sm font-bold text-white leading-tight">{{ Auth::guard('ecommerce')->user()->name }}</span>
                    </div>
                </div>

                <!-- Dropdown Menu -->
                <div class="opacity-0 pointer-events-none scale-95 group-hover/user:opacity-100 group-hover/user:pointer-events-auto group-hover/user:scale-100 transition-all duration-300 origin-top-right absolute top-full right-0 mt-0 w-56 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl py-2 z-50">
                    <div class="px-4 py-3 border-b border-white/10 mb-2 bg-white/5 mx-2 rounded-lg">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">FORGE Points</p>
                        <div class="flex items-end gap-2">
                            <p class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary to-[#ff8c33]">0</p>
                            <p class="text-[10px] font-normal text-gray-500 mb-1 pb-0.5">(For now)</p>
                        </div>
                    </div>
                    <a href="{{ route('ecommerce.account.profile', ['store' => $store]) }}" class="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                        <i class="ph ph-user-circle text-lg text-gray-400"></i> My Account
                    </a>
                    <a href="{{ route('ecommerce.account.order-history', ['store' => $store]) }}#order-history" class="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                        <i class="ph ph-receipt text-lg text-gray-400"></i> Order History
                    </a>

                    <form action="{{ route('ecommerce.logout', ['store' => $store]) }}" method="POST" class="w-full mt-2 border-t border-white/10 pt-2">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full text-left px-5 py-2.5 text-sm font-bold text-red-500 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                            <i class="ph ph-sign-out text-lg"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="hidden lg:flex relative group/guest py-2">
                <a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-user text-xl text-gray-400 group-hover/guest:text-primary transition-colors"></i>
                    <div class="flex flex-col text-left">
                        <span class="text-[10px] text-gray-400 leading-tight">Welcome</span>
                        <span class="text-sm font-bold text-white group-hover/guest:text-primary transition-colors leading-tight">Sign In / Register</span>
                    </div>
                </a>

                <!-- Unauthenticated Dropdown -->
                <div class="opacity-0 pointer-events-none scale-95 group-hover/guest:opacity-100 group-hover/guest:pointer-events-auto group-hover/guest:scale-100 transition-all duration-300 origin-top-right absolute top-full right-0 mt-0 w-64 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-4 z-50">
                    <h4 class="text-sm font-bold text-white mb-2">Join {{ $storefrontName }}</h4>
                    <p class="text-[11px] text-gray-400 mb-4 leading-snug">Earn Forge Points, track orders, and checkout faster.</p>
                    <a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="block w-full bg-gradient-to-r from-primary to-orange-400 hover:from-primary hover:to-orange-500 text-white text-center py-2.5 rounded-xl font-bold text-sm transition-colors mb-2 shadow-[0_0_15px_rgba(255,107,0,0.3)]">Sign In</a>
                    <a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="block w-full bg-white/5 hover:bg-white/10 border border-white/10 text-white text-center py-2.5 rounded-xl font-bold text-sm transition-colors">Create Account</a>
                </div>
            </div>
            @endauth

            <!-- Customer Notification Bell with Hover Dropdown -->
            <style>
                @keyframes notif-pulse-ring {
                    0% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0.5), 0 0 0 0 rgba(255, 107, 0, 0.3); }
                    40% { box-shadow: 0 0 0 10px rgba(255, 107, 0, 0), 0 0 0 20px rgba(255, 107, 0, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0), 0 0 0 0 rgba(255, 107, 0, 0); }
                }
                .customer-notif-pulse {
                    animation: notif-pulse-ring 1.4s ease-out 2;
                    border-color: rgba(255, 107, 0, 0.4) !important;
                }
                .customer-notif-pulse i {
                    color: #ff8c33 !important;
                }
            </style>
            <div id="customer-notif-wrap" class="relative z-30 shrink-0 group">
                <a href="{{ route('ecommerce.notifications', ['store' => $store]) }}" id="customer-notif-btn" class="w-11 h-11 flex items-center justify-center rounded-2xl border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all text-gray-300 hover:text-white relative shrink-0" onmouseenter="this.classList.remove('customer-notif-pulse')">
                    <i class="ph ph-bell text-xl"></i>
                    <span id="customer-notif-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-[5px] flex items-center justify-center text-[9px] font-bold bg-red-500 text-white rounded-full shadow-[0_0_8px_rgba(239,68,68,0.6)]">0</span>
                </a>

                <!-- Notification Dropdown (hover) -->
                <div class="bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 absolute top-[calc(100%+0.5rem)] right-0 w-80 sm:w-96 rounded-2xl overflow-hidden shadow-2xl p-5 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-300 transform group-hover:translate-y-0 -translate-y-2 origin-top">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-white">Notifications</h3>
                        <span id="customer-dd-count" class="bg-primary/20 text-primary text-[10px] font-bold px-2 py-1 rounded-md hidden">0 New</span>
                    </div>

                    <div id="customer-dd-body" class="flex flex-col gap-3 mb-4">
                        <div class="text-center py-6 text-gray-500 text-sm">
                            <i class="ph ph-bell-slash text-2xl mb-2 block"></i>
                            <span>No notifications yet</span>
                        </div>
                    </div>

                    <div class="flex justify-center pt-3 border-t border-white/10 mt-2">
                        <a href="{{ route('ecommerce.notifications', ['store' => $store]) }}" class="text-xs font-bold text-gray-400 hover:text-primary transition-colors">
                            View All Notifications
                        </a>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                var badge = document.getElementById('customer-notif-badge');
                var ddBody = document.getElementById('customer-dd-body');
                var ddCount = document.getElementById('customer-dd-count');
                if (!badge) return;

                @auth('ecommerce')
                var unreadUrl = '{{ route("ecommerce.api.notifications.unread", ["store" => $store]) }}';
                var markAllUrl = '{{ route("ecommerce.api.notifications.mark-all-read", ["store" => $store]) }}';
                var markOneUrl = '{{ route("ecommerce.api.notifications.mark-read", ["store" => $store, "id" => "PLACEHOLDER"]) }}';
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                var pollTimer = null;
                var previousCount = -1;

                function triggerPulse() {
                    var btn = document.getElementById('customer-notif-btn');
                    if (!btn) return;
                    btn.classList.add('customer-notif-pulse');
                    btn.addEventListener('animationend', function() {
                        btn.classList.remove('customer-notif-pulse');
                    }, { once: true });
                }

                function fetchCount() {
                    fetch(unreadUrl, { headers: { 'Accept': 'application/json' } })
                        .then(function(r) { return r.ok ? r.json() : null; })
                        .then(function(resp) {
                            if (!resp || !resp.success) return;
                            var data = resp.data;
                            var count = data.count || 0;

                            // Detect new notification and trigger pulse
                            if (previousCount >= 0 && count > previousCount) {
                                triggerPulse();
                            }
                            previousCount = count;

                            badge.textContent = count;
                            badge.classList.toggle('hidden', count === 0);
                            // Update dropdown content
                            renderDropdown(data.notifications, count);
                        })
                        .catch(function() {});
                }

                function renderDropdown(notifications, count) {
                    // Update badge count in dropdown header
                    if (ddCount) {
                        ddCount.textContent = count + ' New';
                        ddCount.classList.toggle('hidden', count === 0);
                    }
                    if (!ddBody) return;

                    if (!notifications || notifications.length === 0) {
                        ddBody.innerHTML = '<div class="text-center py-6 text-gray-500 text-sm"><i class="ph ph-bell-slash text-2xl mb-2 block"></i><span>All caught up!</span></div>';
                        return;
                    }

                    var html = '';
                    notifications.slice(0, 5).forEach(function(n) {
                        var iconColor = n.icon_color || 'primary';
                        var icon = n.icon || 'ph-megaphone';
                        html += '<a href="' + (n.link || '{{ route("ecommerce.notifications", ["store" => $store]) }}') + '" class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors group/item border border-transparent hover:border-white/5 notif-dd-item" data-id="' + n.id + '">';
                        html += '   <div class="w-10 h-10 rounded-full bg-' + iconColor + '/20 flex items-center justify-center shrink-0">';
                        html += '       <i class="ph-fill ' + icon + ' text-xl text-' + iconColor + '"></i>';
                        html += '   </div>';
                        html += '   <div class="flex-1 min-w-0 pt-0.5">';
                        html += '       <h4 class="text-sm font-bold text-white mb-1 group-hover/item:text-primary transition-colors">' + escapeHtml(n.title) + '</h4>';
                        if (n.body) html += '       <p class="text-xs text-gray-400 leading-relaxed">' + escapeHtml(n.body) + '</p>';
                        html += '       <span class="text-[10px] text-gray-500 mt-2 block">' + timeAgo(n.created_at) + '</span>';
                        html += '   </div>';
                        html += '</a>';
                    });

                    ddBody.innerHTML = html;

                    // Click to mark as read
                    ddBody.querySelectorAll('.notif-dd-item').forEach(function(el) {
                        el.addEventListener('click', function(e) {
                            var id = el.getAttribute('data-id');
                            if (id) {
                                fetch(markOneUrl.replace('PLACEHOLDER', id), {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                                }).catch(function() {});
                            }
                        });
                    });
                }

                function escapeHtml(str) {
                    if (!str) return '';
                    var div = document.createElement('div');
                    div.appendChild(document.createTextNode(str));
                    return div.innerHTML;
                }

                function timeAgo(dateStr) {
                    if (!dateStr) return '';
                    var now = new Date();
                    var date = new Date(dateStr);
                    var diffMs = now - date;
                    var diffSec = Math.floor(diffMs / 1000);
                    if (diffSec < 60) return 'just now';
                    var diffMin = Math.floor(diffSec / 60);
                    if (diffMin < 60) return diffMin + 'm ago';
                    var diffHr = Math.floor(diffMin / 60);
                    if (diffHr < 24) return diffHr + 'h ago';
                    var diffDay = Math.floor(diffHr / 24);
                    if (diffDay < 7) return diffDay + 'd ago';
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }

                function startPolling() {
                    if (pollTimer) clearInterval(pollTimer);
                    fetchCount();
                    pollTimer = setInterval(fetchCount, 30000);
                }

                function stopPolling() {
                    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                }

                // Pause polling when tab is hidden
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) stopPolling(); else startPolling();
                });

                startPolling();
                @endauth
            })();
            </script>

            <!-- Cart Container -->
            <div id="cart-container" class="relative z-30 shrink-0 group/cart py-2">
                <a href="#" id="cart-btn" onclick="event.preventDefault(); toggleMiniCart()" class="flex items-center gap-2 w-auto h-11 px-3 sm:px-4 rounded-2xl border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all text-gray-300 hover:text-white relative">
                    <div class="relative">
                        <i class="ph ph-shopping-cart text-xl"></i>
                        <span id="cart-badge" class="hidden absolute -top-1 -right-1 w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold bg-primary text-white rounded-full">0</span>
                    </div>
                    <div class="hidden sm:flex flex-col text-left ml-1">
                        <span class="text-[10px] text-gray-400 leading-tight">Returns</span>
                        <span class="text-sm font-bold text-white leading-tight">& Cart</span>
                    </div>
                </a>

                            </div>

        </div>
    </nav>

    <!-- Secondary Navigation -->
    <div id="secondary-nav" class="fixed w-[calc(100%-2rem)] sm:w-[calc(100%-3rem)] lg:w-[calc(100%-4rem)] max-w-7xl left-1/2 -translate-x-1/2 {{ $announcement_enabled ? 'top-[136px]' : 'top-[112px]' }} z-[70] hidden md:flex items-center px-6 py-2.5 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-xl transition-all duration-300">
        <div class="flex items-center gap-8 lg:gap-12 text-[10px] font-bold tracking-widest uppercase" id="sec-nav-links-container" data-preview-block="panel-header-nav-buttons-main" data-parent-section="header-nav-buttons">
            @foreach ($links as $index => $link)
                <a href="{{ str_starts_with($link['url'], '#') ? $link['url'] : url($link['url']) }}" id="sec-nav-link-{{ $index }}" class="text-gray-200 hover:text-primary transition-colors py-2" data-preview-block="panel-header-nav-btn-{{ $index + 1 }}" data-parent-section="header-nav-buttons">{{ !empty($link['label']) ? $link['label'] : 'LINK' }}</a>
            @endforeach
        </div>
    </div>



    <script>
        window.ecommerce_routes = {
            cart_add: '{{ route("ecommerce.cart.add", ["store" => $store]) }}',
            cart_count: '{{ route("ecommerce.cart.count", ["store" => $store]) }}'
        };
    </script>
    @vite('Modules/E-Commerce/Store/resources/js/Common/Navbar.js')
    <!-- Mini Cart Drawer -->
    <div id="mini-cart-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[90] opacity-0 pointer-events-none transition-all duration-300" onclick="toggleMiniCart()"></div>

    <div id="mini-cart-drawer" class="fixed top-0 right-0 w-full sm:w-[400px] h-full bg-[#050505] border-l border-white/10 shadow-2xl z-[100] transform translate-x-full transition-transform duration-500 flex flex-col">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between bg-white/5">
            <h2 class="text-lg font-bold text-white tracking-widest uppercase flex items-center gap-3">
                <i class="ph-bold ph-shopping-cart text-primary"></i> Your Cart
            </h2>
            <button onclick="toggleMiniCart()" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition-all">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <!-- Cart Items -->
        <div id="mini-cart-items" class="flex-1 overflow-y-auto p-6 space-y-4">
            <div class="flex flex-col items-center justify-center h-full text-center opacity-50">
                <i class="ph-light ph-shopping-cart text-5xl mb-3 text-gray-400"></i>
                <p class="text-sm font-bold text-white">Your cart is empty.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-white/10 bg-white/5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-widest">Subtotal</span>
                <span id="mini-cart-subtotal" class="text-xl font-black text-white">&#8369;0.00</span>
            </div>
            <a href="{{ route('ecommerce.cart', ['store' => $store]) }}" class="block w-full bg-primary hover:bg-white hover:text-black text-white text-center py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 shadow-[0_0_20px_rgba(255,107,0,0.3)]">
                Checkout Now
            </a>
        </div>
    </div>

    <script>
        window.csrfToken = "{{ csrf_token() }}";

        function toggleMiniCart() {
            const drawer = document.getElementById('mini-cart-drawer');
            const overlay = document.getElementById('mini-cart-overlay');
            if (drawer.classList.contains('translate-x-full')) {
                drawer.classList.remove('translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                drawer.classList.add('translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                overlay.classList.remove('opacity-100', 'pointer-events-auto');
            }
        }

        function updateMiniCartUI(cartCount, items) {
            const badge = document.getElementById('cart-badge');
            if (cartCount > 0) {
                badge.textContent = cartCount;
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }

            const itemsContainer = document.getElementById('mini-cart-items');
            const subtotalEl = document.getElementById('mini-cart-subtotal');

            if (!items || items.length === 0) {
                itemsContainer.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-center opacity-50">
                    <i class="ph-light ph-shopping-cart text-5xl mb-3 text-gray-400"></i>
                    <p class="text-sm font-bold text-white">Your cart is empty.</p>
                </div>`;
                subtotalEl.textContent = '\u20B10.00';
                return;
            }

            let html = '';
            let subtotal = 0;
            items.forEach(item => {
                subtotal += item.price * item.quantity;
                html += `
                <div class="flex items-center gap-4 bg-black/40 border border-white/5 rounded-xl p-3">
                    <div class="w-16 h-16 rounded-lg bg-white/5 flex items-center justify-center p-2 shrink-0">
                        ${item.image_url ? `<img src="${item.image_url}" class="max-w-full max-h-full object-contain">` : `<i class="ph ph-package text-2xl text-gray-500"></i>`}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-white truncate mb-1">${item.name}</h4>
                        <div class="text-xs text-gray-400 mb-1">Qty: ${item.quantity}</div>
                        <div class="text-sm font-bold text-primary">\u20B1${parseFloat(item.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                </div>`;
            });

            itemsContainer.innerHTML = html;
            subtotalEl.textContent = '\u20B1' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        window.addToCart = function(productId, name, price, imageUrl, quantity = 1, productType = 'generic', configuration = null, btn = null) {
            if (typeof price === 'string') {
                price = parseFloat(price.replace(/,/g, ''));
            }

            let originalContent = '';
            if (btn) {
                originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="ph ph-spinner animate-spin text-lg"></i>';
            }

            fetch('{{ route("ecommerce.cart.add", ["store" => $store]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    name: name,
                    price: price,
                    quantity: quantity,
                    image_url: imageUrl,
                    product_type: productType,
                    configuration: configuration
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (btn) {
                        btn.innerHTML = '<i class="ph-bold ph-check text-lg"></i>';
                        btn.classList.add('!bg-green-500', '!border-green-500', '!text-white');
                        setTimeout(() => {
                            btn.innerHTML = originalContent;
                            btn.disabled = false;
                            btn.classList.remove('!bg-green-500', '!border-green-500', '!text-white');
                        }, 2000);
                    }

                    updateMiniCartUI(data.cart_count, data.cart_items);
                    const drawer = document.getElementById('mini-cart-drawer');
                    if (drawer && drawer.classList.contains('translate-x-full')) {
                        toggleMiniCart();
                    }
                } else if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                    // Optionally, you can handle the failure case here, e.g., show an error message.
                    // For now, it just reverts the button.
                }
            })
            .catch(err => {
                console.error('Error adding to cart:', err);
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            });
        }

        // Fetch initial cart count on load
        document.addEventListener('DOMContentLoaded', () => {
            fetch('{{ route("ecommerce.cart.count", ["store" => $store]) }}', {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data) updateMiniCartUI(data.cart_count, data.cart_items);
            })
            .catch(() => {});
        });
    </script>
