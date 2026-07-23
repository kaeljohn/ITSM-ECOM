@php
    $sections = collect($layout['sections'] ?? [])->keyBy('id');
    $enabledSections = collect($layout['sections'] ?? [])->filter(fn (array $section): bool => (bool) ($section['enabled'] ?? false));
    $hero = $sections->get('hero', []);
    $listingsSection = $sections->get('featured_listings', []);
    $promo = $sections->get('promo', []);
    $benefits = $sections->get('benefits', []);
    $store = $company->ecommerce_slug;
    $storefrontUrl = route('ecommerce.home', ['store' => $store]);
    $logoUrl = !empty($layout['logo_path']) ? (str_starts_with($layout['logo_path'], 'Modules/') ? Vite::asset($layout['logo_path']) : asset('storage/'.$layout['logo_path'])) : ($company->logoUrl() ?: asset('ecommerce/Nexora_Logo.png'));

    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $storefrontName = $storefrontCompany?->company_name ?: ($layout['brand_name'] ?? 'Nexora Store');
    $storefrontVisitKey = 'storefront_visited_'.($storefrontCompany?->ecommerce_slug ?: 'store');

    $primaryHex = $layout['primary_color'] ?? '#ff6b00';
    $primaryR = hexdec(substr($primaryHex, 1, 2));
    $primaryG = hexdec(substr($primaryHex, 3, 2));
    $primaryB = hexdec(substr($primaryHex, 5, 2));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <title>{{ $layout['brand_name'] ?? $storefrontName }} | {{ $layout['tagline'] ?? 'Nexora Storefront' }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '{{ $layout['primary_color'] ?? '#ff6b00' }}', hover: '{{ $layout['primary_color'] ?? '#ff6b00' }}CC', glow: '{{ $layout['primary_color'] ?? '#ff6b00' }}80' },
                        accent: '{{ $layout['accent_color'] ?? '#f59e0b' }}',
                        dark: { bg: '#050505', surface: '#121212' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    dropShadow: {
                        'glow': '0 0 15px {{ $layout['primary_color'] ?? '#ff6b00' }}80',
                    },
                    boxShadow: {
                        'glow': '0 0 20px {{ $layout['primary_color'] ?? '#ff6b00' }}4D',
                        'glow-lg': '0 0 30px {{ $layout['primary_color'] ?? '#ff6b00' }}26',
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
            background: radial-gradient(circle, {{ $layout['primary_color'] ?? '#ff6b00' }}59 0%, transparent 65%);
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
            background: radial-gradient(circle, {{ $layout['accent_color'] ?? '#990000' }}66 0%, transparent 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }

        @keyframes floatPulse1 {
            0% { opacity: 0.3; transform: translate(0, 0) scale(0.8); }
            33% { opacity: 0.8; transform: translate(25vw, 15vh) scale(1.2); }
            66% { opacity: 0.4; transform: translate(-10vw, 30vh) scale(0.9); }
            100% { opacity: 0.3; transform: translate(0, 0) scale(0.8); }
        }

        @keyframes floatPulse2 {
            0% { opacity: 0.8; transform: translate(0, 0) scale(1.1); }
            33% { opacity: 0.3; transform: translate(-25vw, -15vh) scale(0.8); }
            66% { opacity: 0.7; transform: translate(15vw, -25vh) scale(1.3); }
            100% { opacity: 0.8; transform: translate(0, 0) scale(1.1); }
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--tw-color-primary); }

        @keyframes spinFastOnce { 0% { transform: rotate(0deg); } 100% { transform: rotate(720deg); } }
        .animate-spin-fast { animation: spinFastOnce 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        @keyframes slideTextOut { 0% { max-width: 0; opacity: 0; padding-left: 0; } 100% { max-width: 400px; opacity: 1; padding-left: 1.5rem; } }
        .animate-slide-text { animation: slideTextOut 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: 0.8s; overflow: hidden; white-space: nowrap; opacity: 0; max-width: 0; }
    </style>

    @vite('Modules/E-Commerce/Techforge/resources/css/liquidglass.css')
</head>
<body class="relative antialiased selection:bg-primary selection:text-white">
    @if (isset($preview) && $preview)
        <div class="sticky top-0 z-[100] bg-amber-500/10 backdrop-blur-md border-b border-amber-500/20 px-5 py-2.5 text-center text-xs tracking-wider font-bold text-amber-400 uppercase shadow-[0_4px_30px_rgba(0,0,0,0.1)]">Preview mode — this draft is not public until you publish it.</div>
    @endif

    <div id="preloader" data-visit-key="{{ $storefrontVisitKey }}" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
        <script>
            if (!sessionStorage.getItem(@json($storefrontVisitKey))) {
                document.write(`
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full animate-pulse"></div>
                        <div class="flex items-center relative z-10">
                            <img src="{{ $logoUrl }}" alt="{{ $storefrontName }} logo" class="h-20 w-auto object-contain animate-spin-fast">
                            <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-slide-text">{{ $storefrontName }}</span>
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

    <div class="ambient-light-1"></div>
    <div class="ambient-light-2"></div>

    <x-navbar :storefrontName="$storefrontName" :store="$store" :logoUrl="$logoUrl" :layout="$layout" />

    <div class="pt-[140px] lg:pt-[180px]">
    @foreach ($enabledSections as $section)
        @if ($section['id'] === 'hero')
            <main data-preview-section="hero" class="relative pb-0 overflow-hidden flex flex-col items-center justify-start mb-20 transition-all duration-300">
                @if($section['particles_enabled'] ?? false)
                <canvas id="hero-particles" class="absolute inset-0 w-full h-full pointer-events-none z-10 opacity-50"></canvas>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const canvas = document.getElementById('hero-particles');
                        if(!canvas) return;
                        const ctx = canvas.getContext('2d');
                        let w = canvas.width = window.innerWidth;
                        let h = canvas.height = canvas.parentElement.clientHeight;
                        window.addEventListener('resize', () => {
                            w = canvas.width = window.innerWidth;
                            h = canvas.height = canvas.parentElement.clientHeight;
                        });
                        const particlesCount = {{ $section['particles_count'] ?? 40 }};
                        const particlesSpeed = {{ $section['particles_speed'] ?? 1.0 }};
                        
                        const particles = Array.from({length: particlesCount}, () => ({
                            x: Math.random() * w,
                            y: Math.random() * h,
                            r: Math.random() * 2 + 1,
                            dx: (Math.random() - 0.5) * 0.5 * particlesSpeed,
                            dy: ((Math.random() - 0.5) * 1 - 0.5) * particlesSpeed,
                            a: Math.random() * 0.5 + 0.1
                        }));
                        function draw() {
                            ctx.clearRect(0,0,w,h);
                            particles.forEach(p => {
                                ctx.beginPath();
                                ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
                                ctx.fillStyle = `rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, ${p.a})`;
                                ctx.fill();
                                p.x += p.dx;
                                p.y += p.dy;
                                if(p.y < -10) p.y = h+10;
                                if(p.x < -10) p.x = w+10;
                                if(p.x > w+10) p.x = -10;
                            });
                            requestAnimationFrame(draw);
                        }
                        draw();
                    });
                </script>
                @endif
                <div class="relative w-full max-w-7xl mx-auto px-6 z-20 flex flex-col lg:flex-row items-center lg:items-center justify-between gap-12 lg:gap-8 flex-grow mb-12 lg:mb-16 mt-10">
                    <div class="w-full lg:w-1/2 flex flex-col justify-center items-start text-left relative z-30">
                        @php
                            $rawTitle = $section['title'] ?? '';
                            if (!empty($section['highlight']) && !str_contains($rawTitle, '{')) {
                                $rawTitle .= '<br>{' . $section['highlight'] . '}';
                            }
                            $parsedTitle = preg_replace('/\{(.*?)\}/', '<span class="text-primary drop-shadow-glow">$1</span>', htmlspecialchars($rawTitle, ENT_QUOTES));
                            $parsedTitle = str_replace('&lt;br&gt;', '<br>', $parsedTitle);
                        @endphp
                        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black uppercase leading-[1.1] tracking-wider text-white mb-8 relative drop-shadow-xl">
                            {!! $parsedTitle !!}
                        </h1>
                        <p class="text-gray-400 text-sm sm:text-base max-w-md leading-relaxed mb-10 font-medium">
                            {{ $section['body'] }}
                        </p>
                        @php
                            $buttons = $section['buttons'] ?? (!empty($section['button_label']) ? [['label' => $section['button_label'], 'url' => $section['button_url'] ?? '#products', 'style' => 'primary']] : []);
                            $alignmentMap = ['start' => 'justify-start', 'center' => 'justify-center', 'end' => 'justify-end'];
                            $btnAlign = $alignmentMap[$section['button_alignment'] ?? 'start'] ?? 'justify-start';
                        @endphp
                        @if (count($buttons) > 0)
                        <div class="flex flex-wrap items-center {{ $btnAlign }} gap-4 mb-4 w-full">
                            @foreach($buttons as $btn)
                                @if(($btn['style'] ?? 'primary') === 'primary')
                                    <a href="{{ $btn['url'] }}" class="bg-primary text-black px-8 py-3.5 font-black hover:bg-white transition-colors uppercase tracking-widest text-xs sm:text-sm shadow-glow hover:shadow-[0_0_30px_rgba(255,255,255,0.5)]">
                                        {{ $btn['label'] }} &rarr;
                                    </a>
                                @else
                                    <a href="{{ $btn['url'] }}" class="bg-transparent border-2 border-white/30 text-white px-8 py-3.5 font-black hover:border-primary hover:text-primary transition-colors uppercase tracking-widest text-xs sm:text-sm">
                                        {{ $btn['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                        @endif
                        @if(!empty($section['cta_subtext']))
                        <p class="text-gray-500 text-xs font-semibold tracking-wide uppercase mb-12">{{ $section['cta_subtext'] }}</p>
                        @endif
                    </div>
                    
                    <div class="w-full lg:w-1/2 flex justify-center lg:justify-end mt-4 lg:mt-0 relative group z-20">
                        <div class="absolute -inset-20 bg-gradient-to-tr from-transparent via-primary/5 to-transparent transform -skew-x-12 pointer-events-none"></div>
                        <div class="flex flex-col gap-6 w-full max-w-[500px]">
                            <div class="relative w-full aspect-[4/3] lg:aspect-[4/5] xl:aspect-square">
                                <div class="absolute inset-0 w-full h-full overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] group/card">
                                    <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-primary z-20 pointer-events-none"></div>
                                    <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-primary z-20 pointer-events-none"></div>
                                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-primary z-20 pointer-events-none"></div>
                                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-primary z-20 pointer-events-none"></div>

                                    @if (!empty($section['image_path']))
                                        <img id="hero-main-img" src="{{ asset('storage/'.$section['image_path']) }}" class="w-full h-full object-cover transition-opacity duration-700 opacity-90 group-hover/card:opacity-100 mix-blend-lighten">
                                    @elseif (isset($customConfigs) && count($customConfigs) > 0)
                                        <img id="hero-main-img" src="{{ $customConfigs[0]->image_url ?? '' }}" class="w-full h-full object-cover transition-opacity duration-700 opacity-90 group-hover/card:opacity-100 mix-blend-lighten">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-primary/30 to-accent/20 flex items-center justify-center opacity-80 group-hover/card:opacity-100"></div>
                                    @endif
                                    
                                    <div class="absolute inset-0 bg-black pointer-events-none transition-opacity duration-500" style="opacity: {{ ($section['overlay_opacity'] ?? 0) / 100 }};"></div>
                                    
                                    @if (($section['visual_style'] ?? 'showcase') === 'gallery' && isset($customConfigs) && count($customConfigs) > 0)
                                    <div class="absolute bottom-0 inset-x-0 h-1/2 bg-gradient-to-t from-[#050505] via-[#050505]/60 to-transparent flex flex-col justify-end p-6 sm:p-8 pointer-events-none z-10">
                                        <div class="flex justify-between items-end w-full">
                                            <div>
                                                <div id="hero-badge" class="text-primary text-[10px] font-black uppercase tracking-widest mb-1">{{ $section['badge_text'] ?? 'FEATURED BUILD' }}</div>
                                                <h3 id="hero-title" class="text-white text-2xl sm:text-3xl font-black uppercase tracking-tight">{{ $customConfigs[0]->name ?? 'PHANTOM V4' }}</h3>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1">FROM</div>
                                                <div id="hero-price" class="text-primary text-xl sm:text-2xl font-black">₱{{ number_format($customConfigs[0]->price ?? 105500, 0) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if (($section['visual_style'] ?? 'showcase') === 'gallery' && isset($customConfigs) && count($customConfigs) > 0)
                            <div class="w-full flex justify-between gap-2 sm:gap-3 z-40 overflow-x-hidden" id="hero-thumbnails-container">
                                @foreach($customConfigs as $index => $config)
                                <button data-tier="{{ strtolower($config->tier) }}" class="hero-thumbnail flex-1 h-14 sm:h-20 {{ $index === 0 ? 'border-2 border-primary shadow-[0_0_20px_rgba(255,107,0,0.2)]' : 'border border-white/20 hover:border-primary/50' }} bg-[#050505] relative overflow-hidden group cursor-pointer transition-colors rounded-lg">
                                    <img src="{{ $config->image_url }}" class="w-full h-full object-cover mix-blend-lighten {{ $index === 0 ? 'opacity-90' : 'opacity-40 group-hover:opacity-80 grayscale group-hover:grayscale-0' }} transition-opacity">
                                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black via-black/80 to-transparent p-2">
                                        <div class="text-[8px] sm:text-[10px] font-black tracking-widest uppercase text-center {{ $index === 0 ? 'text-white' : 'text-gray-400 group-hover:text-white' }}">{{ $config->tier }}</div>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const tiersData = {
                                        @foreach($customConfigs as $config)
                                        '{{ strtolower($config->tier) }}': {
                                            title: '{{ addslashes($config->name) }}',
                                            price: '₱{{ number_format($config->price, 0) }}',
                                            image: '{{ $config->image_url }}'
                                        },
                                        @endforeach
                                    };

                                    const thumbnails = document.querySelectorAll('.hero-thumbnail');
                                    const mainImg = document.getElementById('hero-main-img');
                                    const titleEl = document.getElementById('hero-title');
                                    const priceEl = document.getElementById('hero-price');
                                    
                                    let autoScrollInterval;
                                    
                                    function startAutoScroll() {
                                        const cycleSecs = {{ $section['gallery_cycle'] ?? 5 }};
                                        const cycleIntervalMs = (cycleSecs > 0 ? cycleSecs : 5) * 1000;
                                        
                                        autoScrollInterval = setInterval(() => {
                                            let activeIndex = -1;
                                            thumbnails.forEach((t, index) => {
                                                if (t.classList.contains('border-primary')) activeIndex = index;
                                            });
                                            
                                            if (activeIndex !== -1) {
                                                let nextIndex = (activeIndex + 1) % thumbnails.length;
                                                thumbnails[nextIndex].click();
                                            }
                                        }, cycleIntervalMs);
                                    }
                                    
                                    startAutoScroll();

                                    thumbnails.forEach((thumb) => {
                                        thumb.addEventListener('click', function() {
                                            clearInterval(autoScrollInterval);
                                            startAutoScroll();

                                            const tier = this.getAttribute('data-tier');
                                            const data = tiersData[tier];

                                            if(data) {
                                                if(titleEl) titleEl.textContent = data.title;
                                                if(priceEl) priceEl.textContent = data.price;

                                                if(mainImg) {
                                                    mainImg.style.transition = 'opacity 0.3s ease-in-out';
                                                    mainImg.style.opacity = 0;
                                                    setTimeout(() => {
                                                        mainImg.src = data.image;
                                                        mainImg.style.opacity = 1;
                                                    }, 300);
                                                }

                                                thumbnails.forEach(t => {
                                                    t.className = 'hero-thumbnail flex-1 h-14 sm:h-20 border border-white/20 bg-[#050505] relative overflow-hidden group cursor-pointer hover:border-primary/50 transition-colors rounded-lg';
                                                    const img = t.querySelector('img');
                                                    if(img) img.className = 'w-full h-full object-cover mix-blend-lighten opacity-40 group-hover:opacity-80 transition-opacity grayscale group-hover:grayscale-0';
                                                    const text = t.querySelector('div > div');
                                                    if(text) text.className = 'text-[8px] sm:text-[10px] font-black tracking-widest uppercase text-gray-400 group-hover:text-white text-center';
                                                });

                                                this.className = 'hero-thumbnail flex-1 h-14 sm:h-20 border-2 border-primary bg-[#050505] relative overflow-hidden group cursor-pointer shadow-[0_0_20px_rgba(255,107,0,0.2)] rounded-lg';
                                                const activeImg = this.querySelector('img');
                                                if(activeImg) activeImg.className = 'w-full h-full object-cover mix-blend-lighten opacity-90 group-hover:opacity-100 transition-opacity transform group-hover:scale-110 duration-700';
                                                const activeText = this.querySelector('div > div');
                                                if(activeText) activeText.className = 'text-white text-[8px] sm:text-[10px] font-black tracking-widest uppercase text-center';
                                            }
                                        });
                                    });
                                });
                            </script>
                            @endif
                        </div>
                    </div>
                </div>
            </main>

        @elseif ($section['id'] === 'benefits')
            <div data-preview-section="benefits" class="w-full relative z-20 mt-auto overflow-hidden py-3 liquid-glass border-y border-white/5 backdrop-blur-xl mb-24 transition-all duration-300">
                <div class="w-full h-full flex" style="mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);">
                    <div class="flex animate-marquee items-center w-max">
                        @for($i=0; $i<4; $i++)
                        <div class="flex items-center gap-6 sm:gap-12 px-3 sm:px-6">
                            @foreach(['benefit_one', 'benefit_two', 'benefit_three'] as $benefitKey)
                            <div class="flex items-center gap-6 sm:gap-12">
                                <span class="text-[9px] sm:text-[11px] font-bold text-gray-400 uppercase tracking-[0.3em] whitespace-nowrap">{{ $section[$benefitKey] ?? 'BENEFIT' }}</span>
                                <div class="w-1.5 h-1.5 bg-primary transform rotate-45 shadow-[0_0_5px_rgba(255,107,0,0.5)]"></div>
                            </div>
                            @endforeach
                        </div>
                        @endfor
                    </div>
                </div>
            </div>

        @elseif ($section['id'] === 'featured_listings')
            @if(isset($storefrontListings) && $storefrontListings->isNotEmpty())
            <section data-preview-section="featured_listings" id="products" class="max-w-7xl mx-auto px-6 lg:px-8 mb-24 relative z-10 pt-10 transition-all duration-300">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <p class="text-primary text-xs font-black tracking-[0.3em] uppercase mb-2">Available now</p>
                        <h2 class="text-3xl md:text-4xl font-black uppercase">{{ $section['title'] }}</h2>
                    </div>
                    <span class="text-xs text-gray-400">{{ $section['body'] }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($storefrontListings as $listing)
                    <a href="{{ route('ecommerce.listings.show', ['store' => $store, 'listing' => $listing]) }}" class="rounded-2xl p-5 bg-white/5 border border-white/10 hover:border-primary/70 transition">
                        <div class="h-36 rounded-xl bg-black/40 flex items-center justify-center overflow-hidden">
                            @if($listing->image_url)
                                <img class="max-h-full object-contain" src="{{ asset('storage/'.$listing->image_url) }}" alt="{{ $listing->name }}">
                            @endif
                        </div>
                        <h3 class="font-bold mt-4">{{ $listing->name }}</h3>
                        <p class="text-primary font-black text-xl mt-2">₱{{ number_format((float) $listing->price, 2) }}</p>
                        <p class="text-xs text-emerald-400 mt-2">{{ $listing->available_quantity }} available</p>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

        @elseif ($section['id'] === 'promo')
            <section data-preview-section="promo" id="about" class="max-w-7xl mx-auto px-6 lg:px-8 mb-32 relative z-10 pt-10 transition-all duration-300">
                <div class="liquid-glass backdrop-blur-2xl bg-black/40 rounded-2xl border border-white/5 p-10 md:p-16 flex flex-col items-center text-center relative overflow-hidden group hover:border-primary/50 transition-all duration-500 hover:shadow-glow-lg">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <h2 class="text-3xl sm:text-5xl font-black text-white uppercase tracking-tight mb-6 relative z-10">{{ $section['title'] }}</h2>
                    <p class="text-gray-400 text-sm sm:text-base max-w-2xl font-medium mb-10 relative z-10">{{ $section['body'] }}</p>
                    @php
                        $promoBtns = $section['buttons'] ?? (!empty($section['button_label']) ? [['label' => $section['button_label'], 'url' => $section['button_url'] ?? '#products', 'style' => 'primary']] : []);
                        $alignmentMap = ['start' => 'justify-start', 'center' => 'justify-center', 'end' => 'justify-end'];
                        $promoAlign = $alignmentMap[$section['button_alignment'] ?? 'center'] ?? 'justify-center';
                    @endphp
                    @if (count($promoBtns) > 0)
                    <div class="relative z-10 flex flex-wrap items-center {{ $promoAlign }} gap-4 w-full">
                        @foreach($promoBtns as $btn)
                            @if(($btn['style'] ?? 'primary') === 'primary')
                                <a href="{{ $btn['url'] }}" class="bg-primary text-black px-8 py-3.5 font-black hover:bg-white transition-colors uppercase tracking-widest text-xs sm:text-sm shadow-glow hover:shadow-[0_0_30px_rgba(255,255,255,0.5)]">
                                    {{ $btn['label'] }}
                                </a>
                            @else
                                <a href="{{ $btn['url'] }}" class="bg-transparent border-2 border-white/30 text-white px-8 py-3.5 font-black hover:border-primary hover:text-primary transition-colors uppercase tracking-widest text-xs sm:text-sm">
                                    {{ $btn['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </section>
        @endif
    @endforeach
    </div>

    <x-footer :storefrontName="$storefrontName" :store="$store" :logoUrl="$logoUrl" />

    @vite(['Modules/E-Commerce/Techforge/resources/js/Common/Preloader.js', 'Modules/E-Commerce/Techforge/resources/js/Common/AmbientEffects.js'])
    @vite('Modules/E-Commerce/Techforge/resources/js/HomePage/Homepage.js')
    
    @if(request()->routeIs('ecommerce.admin.layout.preview'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('[data-preview-section]');
            sections.forEach(section => {
                section.style.cursor = 'pointer';
                section.title = 'Click to edit this section';
                
                section.addEventListener('mouseover', (e) => {
                    e.stopPropagation();
                    section.style.outline = '3px solid #1B6FC8';
                    section.style.outlineOffset = '4px';
                    section.style.borderRadius = '16px';
                    section.style.boxShadow = '0 0 30px rgba(27,111,200,0.3)';
                    section.style.zIndex = '50';
                });
                
                section.addEventListener('mouseout', () => {
                    section.style.outline = '';
                    section.style.outlineOffset = '';
                    section.style.borderRadius = '';
                    section.style.boxShadow = '';
                    section.style.zIndex = '';
                });
                
                section.addEventListener('click', (e) => {
                    if (!e.isTrusted) return; // Prevent programmatic clicks (like gallery auto-scroll) from triggering parent scroll
                    
                    e.preventDefault();
                    e.stopPropagation();
                    window.parent.postMessage({
                        action: 'select_section',
                        section: section.dataset.previewSection
                    }, '*');
                });
            });
        });
    </script>
    @endif
</body>
</html>
