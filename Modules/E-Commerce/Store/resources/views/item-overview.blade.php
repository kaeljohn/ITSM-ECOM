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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <title>{{ $storefrontName }} | {{ $product->name }}</title>
    
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
            background: radial-gradient(circle, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.15) 0%, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
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
        
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    @vite('Modules/E-Commerce/Store/resources/css/liquidglass.css')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
</head>
<body class="relative antialiased selection:bg-primary selection:text-white">

    @vite('Modules/E-Commerce/Store/resources/js/Common/Preloader.js')

    <!-- Background Ambient Effects -->
    <div class="ambient-light-1"></div>

    <x-navbar />

    <main class="flex-grow container mx-auto px-4 pt-48 pb-16 lg:pt-56 lg:pb-20 relative z-10 max-w-7xl">
        
        @php
            $isOnSale = rand(0, 1) == 1; // 50% chance to be on sale
            $originalPrice = $product->price + (floor(rand(5000, 15000) / 1000) * 1000);
            $rating = rand(40, 50) / 10;
            $reviewCount = rand(10, 250);
            $saveAmount = $originalPrice - $product->price;
            
            // Dummy image thumbnails
            $mainImg = $product->image_url ?? 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=800&q=80';
            $thumbnails = [
                $mainImg,
                'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1555680202-c86f0e12f086?auto=format&fit=crop&w=800&q=80'
            ];
            
            $shortSpecs = [
                ['label' => 'SKU', 'value' => $product->sku ?? 'N/A'],
                ['label' => 'Category', 'value' => $product->category ?? 'General'],
                ['label' => 'Availability', 'value' => ($product->available_quantity ?? 0) > 0 ? 'In Stock' : 'Out of Stock'],
                ['label' => 'Stock', 'value' => ($product->available_quantity ?? 0) . ' units available'],
                ['label' => 'Warranty', 'value' => '1 Year Standard Warranty'],
            ];
        @endphp

        <div class="flex flex-col lg:flex-row gap-12 mb-20">
            <!-- Left Column: Hero Images -->
            <div class="w-full lg:w-1/2 flex flex-col gap-4">
                <!-- Main Image -->
                <div data-tilt data-tilt-max="5" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.2" class="liquid-glass rounded-3xl p-8 border border-white/10 aspect-square flex items-center justify-center relative overflow-hidden bg-gradient-to-br from-black/40 to-black/80" style="transform-style: preserve-3d;">
                    <img id="main-product-image" src="{{ $mainImg }}" alt="{{ $product->name }}" loading="lazy" class="lazy-img w-full h-full object-contain drop-shadow-2xl transition-transform duration-500" style="transform: translateZ(20px);">
                </div>
                <!-- Thumbnails -->
                <div class="flex gap-4 overflow-x-auto hide-scrollbar pb-2">
                    @foreach($thumbnails as $idx => $thumb)
                        <div onclick="updateMainImage(this, '{{ $thumb }}')" class="thumbnail-btn cursor-pointer w-24 h-24 shrink-0 rounded-2xl p-2 border {{ $idx === 0 ? 'border-primary bg-primary/10' : 'border-white/10 bg-black/40 hover:border-white/30' }} transition-all">
                            <img src="{{ $thumb }}" loading="lazy" class="lazy-img w-full h-full object-cover rounded-xl" alt="Thumbnail {{ $idx }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: Product Details -->
            <div class="w-full lg:w-1/2 flex flex-col">
                <h1 class="text-3xl md:text-5xl font-black text-white leading-tight mb-6">{{ $product->name }}</h1>
                
                <hr class="border-white/10 mb-6">
                
                <div class="flex flex-col gap-2 mb-6">
                    @if($isOnSale)
                        <div class="bg-primary/20 border border-primary/30 text-primary px-3 py-1 rounded-md w-max text-xs font-black uppercase tracking-widest">
                            Save ₱{{ number_format($saveAmount) }}
                        </div>
                    @endif
                    
                    <div class="flex items-end gap-4">
                        <span class="text-4xl font-black text-white">₱{{ number_format($product->price) }}</span>
                        @if($isOnSale)
                            <span class="text-xl text-gray-500 line-through mb-1">₱{{ number_format($originalPrice) }}</span>
                        @endif
                    </div>
                </div>

                <hr class="border-white/10 mb-6">

                <div class="flex items-center gap-2 mb-6">
                    <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-green-500 font-bold tracking-wider uppercase text-sm">In Stock & Ready to Ship</span>
                </div>
                
                <div class="bg-black/30 border border-white/5 rounded-xl p-4 mb-6 flex items-center gap-4 shadow-lg group hover:border-primary/30 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center shrink-0 border border-blue-500/30 group-hover:bg-primary/10 group-hover:border-primary/30 group-hover:text-primary transition-colors">
                        <i class="ph-bold ph-truck text-blue-500 text-xl group-hover:text-primary transition-colors"></i>
                    </div>
                    <div>
                        <div class="text-gray-200 text-sm">Order within <span class="text-white font-bold">2 hrs 14 mins</span></div>
                        <div class="text-xs text-gray-500">For expected delivery by <span class="text-white font-bold">{{ now()->addDays(3)->format('l, F j') }}</span></div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-white uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="ph-bold ph-file-text text-primary"></i> Description
                    </h3>
                    <p class="text-gray-300 leading-relaxed text-base">
                        {{ $product->description ?? 'No description available for this product.' }}
                    </p>
                </div>

                <hr class="border-white/10 mb-6">

                <!-- Short Specs -->
                <div class="space-y-3 mb-8">
                    @foreach($shortSpecs as $spec)
                    <div class="flex gap-4 text-sm">
                        <span class="text-gray-500 font-bold w-24 shrink-0">{{ $spec['label'] }}</span>
                        <span class="text-gray-200">{{ $spec['value'] }}</span>
                    </div>
                    @endforeach
                </div>

                <hr class="border-white/10 mb-6">

                <!-- Actions -->
                <div class="flex flex-col gap-4 mb-8">
                    <div class="flex items-center gap-4">
                        <!-- Quantity -->
                        <div class="flex items-center border border-white/10 rounded-xl bg-black/40 h-14 w-32">
                            <button onclick="decrementQty()" class="w-10 h-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 rounded-l-xl transition-colors"><i class="ph-bold ph-minus"></i></button>
                            <input type="number" id="qty" value="1" min="1" max="10" class="flex-1 bg-transparent text-center text-white font-bold outline-none border-none pointer-events-none appearance-none">
                            <button onclick="incrementQty()" class="w-10 h-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 rounded-r-xl transition-colors"><i class="ph-bold ph-plus"></i></button>
                        </div>
                        
                        <button type="button" id="main-add-to-cart" onclick="window.addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, '{{ $product->image_url }}', parseInt(document.getElementById('qty').value) || 1, '{{ $product->bom_id ? 'bom_listing' : 'generic' }}', {!! $product->bom_id ? "{ bom_id: " . (int)$product->bom_id . ", listing_id: '" . $product->id . "' }" : 'null' !!}, event.currentTarget)" class="flex-1 h-14 bg-primary hover:bg-white hover:text-black text-white rounded-xl font-black uppercase tracking-widest flex items-center justify-center gap-3 transition-all duration-300 shadow-[0_0_20px_rgba(255,107,0,0.3)] hover:shadow-[0_0_30px_rgba(255,255,255,0.5)] group">
                            <i class="ph-bold ph-shopping-cart text-xl group-hover:scale-110 transition-transform"></i> Add To Cart
                        </button>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="flex items-center gap-4">
                    <div class="flex text-primary text-lg">
                        @for($i = 1; $i <= 5; $i++)
                            @if($rating >= $i)
                                <i class="ph-fill ph-star"></i>
                            @elseif($rating >= $i - 0.5)
                                <i class="ph-fill ph-star-half"></i>
                            @else
                                <i class="ph-fill ph-star text-gray-600"></i>
                            @endif
                        @endfor
                    </div>
                    <span class="text-white font-bold">{{ number_format($rating, 1) }}</span>
                    <span class="text-gray-500 text-sm">({{ $reviewCount }} Reviews)</span>
                </div>
            </div>
        </div>

        <!-- Suggested Items -->
        @php
            $suggestedItems = \Modules\Ecommerce\Models\StorefrontListing::where('status', 'active')
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->limit(4)
                ->get();
        @endphp
        <div class="max-w-7xl mx-auto mt-20">
            <h2 class="text-2xl font-black text-white mb-8 uppercase tracking-widest flex items-center gap-3">
                <i class="ph-bold ph-arrows-counter-clockwise text-primary"></i> You Might Also Like
            </h2>
            @if($suggestedItems->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($suggestedItems as $suggested)
                    <a href="{{ route('ecommerce.listings.show', ['store' => $store, 'listing' => $suggested->id]) }}" class="group liquid-glass rounded-2xl p-4 border border-white/5 hover:border-primary/50 hover:shadow-glow-lg transition-all duration-500 flex flex-col">
                        <div class="aspect-square w-full rounded-xl bg-black/40 mb-3 flex items-center justify-center p-3 border border-white/5 overflow-hidden">
                            <img src="{{ $suggested->image_url ? asset('storage/' . $suggested->image_url) : 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=800&q=80' }}" alt="{{ $suggested->name }}" loading="lazy" class="lazy-img max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-sm font-bold text-white truncate mb-1 group-hover:text-primary transition-colors">{{ $suggested->name }}</h3>
                        <div class="text-primary font-black text-sm mt-auto">₱{{ number_format($suggested->price) }}</div>
                    </a>
                    @endforeach
                </div>
            @else
                <div class="bg-[#050505]/50 border border-white/5 rounded-2xl p-12 text-center backdrop-blur-md">
                    <i class="ph-bold ph-package text-4xl text-gray-600 mb-4 block"></i>
                    <p class="text-gray-500 text-lg font-medium">No suggested items available at the moment.</p>
                </div>
            @endif
        </div>


        <!-- Sticky Action Bar -->
        <div id="sticky-bar" class="fixed bottom-0 left-0 right-0 z-50 transform translate-y-full transition-transform duration-500">
            <div class="bg-[#0f0f0f]/90 backdrop-blur-xl border-t border-white/10 px-4 py-4 flex items-center justify-between shadow-[0_-10px_30px_rgba(0,0,0,0.5)]">
                <div class="flex items-center gap-4 max-w-7xl mx-auto w-full justify-between">
                    <div class="flex items-center gap-4">
                        <img src="{{ $mainImg }}" loading="lazy" class="lazy-img w-12 h-12 rounded-lg object-cover hidden sm:block border border-white/10">
                        <div>
                            <h3 class="text-white font-bold">{{ $product->name }}</h3>
                            <div class="flex items-center gap-2">
                                <div class="text-primary font-black text-sm">₱{{ number_format($product->price) }}</div>
                                <div class="text-[10px] text-green-500 uppercase tracking-widest font-bold hidden sm:block">In Stock</div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="window.addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, '{{ $product->image_url }}', 1, '{{ $product->bom_id ? 'bom_listing' : 'generic' }}', {!! $product->bom_id ? "{ bom_id: " . (int)$product->bom_id . ", listing_id: '" . $product->id . "' }" : 'null' !!}, event.currentTarget)" class="bg-primary hover:bg-white hover:text-black text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest transition-all duration-300 shadow-[0_0_15px_rgba(255,107,0,0.3)]">
                        Add To Cart
                    </button>
                </div>
            </div>
        </div>
    </main>

    <x-footer />

    <script>
        let isScrolling = false;
        window.addEventListener('scroll', () => {
            if (!isScrolling) {
                window.requestAnimationFrame(() => {
                    const stickyBar = document.getElementById('sticky-bar');
                    const footer = document.querySelector('footer');
                    const mainBtn = document.getElementById('main-add-to-cart');
                    
                    let isFooterVisible = false;
                    if (footer) {
                        const rect = footer.getBoundingClientRect();
                        // Check if the top of the footer is visible in the viewport
                        isFooterVisible = rect.top < window.innerHeight;
                    }

                    let isMainBtnPast = false;
                    if (mainBtn) {
                        const btnRect = mainBtn.getBoundingClientRect();
                        // We only show the sticky bar if the main button has scrolled completely ABOVE the viewport
                        // meaning the user can no longer see the main add to cart button
                        isMainBtnPast = btnRect.bottom < 0;
                    } else {
                        isMainBtnPast = window.scrollY > 600;
                    }

                    if (isMainBtnPast && !isFooterVisible) {
                        stickyBar.classList.remove('translate-y-full');
                    } else {
                        stickyBar.classList.add('translate-y-full');
                    }
                    isScrolling = false;
                });
                isScrolling = true;
            }
        }, { passive: true });

        function updateMainImage(btn, src) {
            document.getElementById('main-product-image').src = src;
            
            document.querySelectorAll('.thumbnail-btn').forEach(el => {
                el.classList.remove('border-primary', 'bg-primary/10');
                el.classList.add('border-white/10', 'bg-black/40');
            });
            
            btn.classList.remove('border-white/10', 'bg-black/40');
            btn.classList.add('border-primary', 'bg-primary/10');
        }

        function incrementQty() {
            let input = document.getElementById('qty');
            if (parseInt(input.value) < 10) input.value = parseInt(input.value) + 1;
        }

        function decrementQty() {
            let input = document.getElementById('qty');
            if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
        }
    </script>
    
    <!-- Lenis Smooth Scroll -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.39/dist/lenis.min.js"></script>
    <script>
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            mouseMultiplier: 1,
            smoothTouch: false,
            touchMultiplier: 2,
            infinite: false,
        });
        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);
    </script>
</body>
</html>
