<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Search Results - {{ $brandName }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:100,200,300,400,500,600,700,800,900" rel="stylesheet" />
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

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
                    dropShadow: { glow: '0 0 15px {{ $primaryHex }}80' },
                    boxShadow: {
                        glow: '0 0 20px {{ $primaryHex }}4D',
                        'glow-lg': '0 0 30px {{ $primaryHex }}26',
                    }
                }
            }
        };
    </script>

    <style id="dynamic-theme-vars">
        :root {
            --theme-primary: {{ $primaryHex }};
            --theme-primary-rgb: {{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }};
            --theme-accent: {{ $accentHex }};
            --theme-accent-rgb: {{ $accentR }}, {{ $accentG }}, {{ $accentB }};
        }
        .text-primary { color: var(--theme-primary) !important; }
        .bg-primary { background-color: var(--theme-primary) !important; }
        .border-primary { border-color: var(--theme-primary) !important; }
        .text-accent { color: var(--theme-accent) !important; }
        .bg-accent { background-color: var(--theme-accent) !important; }
        .border-accent { border-color: var(--theme-accent) !important; }
        .shadow-glow { box-shadow: 0 0 20px rgba(var(--theme-primary-rgb), 0.5) !important; }
        .shadow-glow-lg { box-shadow: 0 0 30px rgba(var(--theme-primary-rgb), 0.35) !important; }
        .shadow-glow-sm { box-shadow: 0 0 10px rgba(var(--theme-primary-rgb), 0.4) !important; }
        .drop-shadow-glow { filter: drop-shadow(0 0 15px rgba(var(--theme-primary-rgb), 0.5)) !important; }
        .ambient-light-1 { background: radial-gradient(circle, rgba(var(--theme-primary-rgb), 0.35) 0%, transparent 65%) !important; }
        .ambient-light-2 { background: radial-gradient(circle, rgba(var(--theme-accent-rgb), 0.4) 0%, transparent 65%) !important; }
    </style>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
        }

        .ambient-light-1 {
            position: fixed;
            top: -20%;
            left: -20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, {{ $primaryHex }}59 0%, transparent 65%);
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
            background: radial-gradient(circle, {{ $accentHex }}66 0%, transparent 65%);
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--theme-primary); }
    </style>

    @vite('Modules/E-Commerce/Store/resources/css/liquidglass.css')
</head>
<body class="relative antialiased selection:bg-primary selection:text-white">

    <!-- Background Ambient Effects -->
    <div class="ambient-light-1"></div>
    <div class="ambient-light-2"></div>

    <x-navbar :storefrontName="$brandName" :store="$store" :logoUrl="$logoUrl" :layout="$layout" />

    <!-- Results Header (below secondary nav) -->
    <main class="relative pt-40 lg:pt-48 pb-6 overflow-hidden w-full">
        <div class="max-w-[1500px] mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-wide">
                        <span class="text-primary">{{ $totalResults }}</span> Results for "{{ $query }}"
                    </h1>
                    <p class="text-gray-400 text-sm mt-1">Searching across <span class="text-white font-bold">{{ $brandName }}</span></p>
                </div>

                <!-- Sort By -->
                <form method="GET" action="{{ route('ecommerce.search', ['store' => $store]) }}" class="flex items-center gap-3 shrink-0">
                    <input type="hidden" name="q" value="{{ $query }}">
                    <span class="text-xs text-gray-500 uppercase tracking-widest font-bold">Sort By</span>
                    <div class="relative w-48">
                        <select name="sort" onchange="this.form.submit()" class="w-full bg-black/40 border border-white/10 rounded-xl py-2 pl-4 pr-10 text-sm text-white appearance-none cursor-pointer hover:border-white/30 transition-colors focus:outline-none focus:border-primary">
                            <option value="Recommended" {{ request('sort') == 'Recommended' ? 'selected' : '' }}>Newest</option>
                            <option value="Price: Low to High" {{ request('sort') == 'Price: Low to High' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="Price: High to Low" {{ request('sort') == 'Price: High to Low' ? 'selected' : '' }}>Price: High to Low</option>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Product Grid -->
    <div class="max-w-[1500px] mx-auto px-6 lg:px-8 pb-24 relative z-10">
        @if($listings->isEmpty())
            <div class="py-20 flex flex-col items-center justify-center text-center bg-white/5 rounded-2xl border border-white/10">
                <i class="ph ph-magnifying-glass text-6xl text-gray-600 mb-6"></i>
                <h3 class="text-2xl font-bold text-white mb-2">No items found</h3>
                <p class="text-gray-400">Try adjusting your search or filters.</p>
            </div>
        @else
            <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($listings as $listing)
                <div class="bg-gradient-to-b from-white/5 to-transparent border border-white/10 rounded-2xl p-4 relative overflow-hidden group hover:border-primary/50 transition-all duration-500 hover:shadow-glow-lg flex flex-col h-full">
                    <div class="relative rounded-xl overflow-hidden aspect-[4/3] mb-5 bg-black/40">
                        @if($listing->image_url)
                            <img src="{{ asset('storage/' . $listing->image_url) }}" alt="{{ $listing->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 opacity-90 group-hover:opacity-100">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="ph ph-package text-5xl text-gray-600"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-white group-hover:text-primary transition-colors line-clamp-2 mb-3">{{ $listing->name }}</h3>
                        @if($listing->description)
                            <p class="text-gray-400 text-sm leading-relaxed line-clamp-2 mb-4">{{ $listing->description }}</p>
                        @endif
                        <div class="mt-auto pt-4 border-t border-white/10">
                            <div class="flex items-end justify-between">
                                <div>
                                    <span class="text-xl font-black text-white">P{{ number_format($listing->price, 2) }}</span>
                                </div>                        <a href="{{ route('ecommerce.listings.show', ['store' => $store, 'listing' => $listing->id]) }}" class="py-2 px-4 rounded-full border border-primary text-primary hover:bg-primary hover:text-white font-bold transition-all duration-300 text-center flex items-center gap-2 text-sm">
                                    <i class="ph-bold ph-arrow-right"></i> Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-footer :storefrontName="$brandName" :store="$store" :logoUrl="$logoUrl" :layout="$layout" />
    
    <script>
        window.appUrl = "{{ url('/') }}";
    </script>
</body>
</html>
