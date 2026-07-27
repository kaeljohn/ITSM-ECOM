@props(['storefrontName' => null, 'logoUrl' => null, 'layout' => []])
@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');

    if ($storefrontCompany) {
        $isPreview = request()->boolean('preview') && \Illuminate\Support\Facades\Auth::guard('ecommerce_admin')->check();
        $publishedLayout = $isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($storefrontCompany) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($storefrontCompany);
        $layout = empty($layout) ? $publishedLayout : $layout;
        $storefrontName = $storefrontName ?? ($publishedLayout['brand_name'] ?? 'Nexora Store');
        $logoUrl = $logoUrl ?? (!empty($publishedLayout['logo_path']) ? (str_starts_with($publishedLayout['logo_path'], 'Modules/') ? Vite::asset($publishedLayout['logo_path']) : asset('storage/'.$publishedLayout['logo_path'])) : null);
    } else {
        // Fallback for preview mode or when middleware doesn't set it
        $storefrontName = $storefrontName ?? 'Nexora Store';
        $logoUrl = $logoUrl ?? null;
    }

    $footer = $layout['footer'] ?? [];
    $footerDescription = $footer['description'] ?? 'Performance-driven computers and accessories for every digital journey.';
    $footerCopyright = $footer['copyright_text'] ?? 'All rights reserved.';
    $col1Title = $footer['column_1_title'] ?? 'Shop';
    $col2Title = $footer['column_2_title'] ?? 'Support';
    $col3Title = $footer['column_3_title'] ?? 'Company';
    $socialInstagram = $footer['social_instagram'] ?? '#';
    $socialTwitter = $footer['social_twitter'] ?? '#';
    $socialFacebook = $footer['social_facebook'] ?? '#';
    $socialYoutube = $footer['social_youtube'] ?? '#';

    $shopLinks = $footer['shop_links'] ?? [
        ['label' => 'Collections', 'url' => '/collections'],
        ['label' => 'Category 1', 'url' => '/categories/category1'],
        ['label' => 'Category 2', 'url' => '/categories/category2'],
        ['label' => 'Category 3', 'url' => '/categories/category3'],
    ];
@endphp
    <!-- Footer -->
    <footer data-preview-section="wrapper-footer" class="border-t border-white/5 pt-16 pb-8 mt-auto relative z-10 liquid-glass bg-black/60 backdrop-blur-2xl">
        <div class="max-w-7xl mx-auto px-10 sm:px-12 lg:px-14">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 mb-12">

                <!-- Brand -->
                <div class="col-span-1 lg:pr-8" data-preview-block="panel-footer-brand">
                    <a href="#" class="flex items-center gap-3 mb-4">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $storefrontName }} Logo" class="h-7 w-auto object-contain flex-shrink-0">
                        @endif
                        <span class="text-xl font-bold tracking-wide text-white uppercase">{{ $storefrontName }}</span>
                    </a>
                    <p id="footer-description-el" class="text-gray-500 text-xs leading-relaxed mb-6">
                        {{ $footerDescription }}
                    </p>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10 w-max">
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest font-semibold">Powered by</span>
                        <img src="{{ Vite::asset('Modules/E-Commerce/Store/resources/img/Nexora_Logo.png') }}" alt="Nexora Logo" class="h-5 w-auto object-contain opacity-80">
                    </div>
                </div>

                <!-- Links 1 (Shop) -->
                <div>
                    <h4 id="footer-col1-title-el" data-preview-block="panel-footer-columns" class="text-primary font-black text-xs tracking-widest uppercase mb-6">{{ $col1Title }}</h4>
                    <ul id="footer-shop-links-list" data-preview-block="panel-footer-shop-links" class="space-y-4 text-[13px] text-gray-400 font-medium">
                        <li><a href="/collections" class="hover:text-white transition-colors">{{ $shopLinks[0]['label'] ?? 'Collections' }}</a></li>
                        <li><a href="/categories/category1" class="hover:text-white transition-colors">{{ $shopLinks[1]['label'] ?? 'Category 1' }}</a></li>
                        <li><a href="/categories/category2" class="hover:text-white transition-colors">{{ $shopLinks[2]['label'] ?? 'Category 2' }}</a></li>
                        <li><a href="/categories/category3" class="hover:text-white transition-colors">{{ $shopLinks[3]['label'] ?? 'Category 3' }}</a></li>
                    </ul>
                </div>

                <!-- Links 2 (Support) -->
                <div>
                    <h4 id="footer-col2-title-el" class="text-primary font-black text-xs tracking-widest uppercase mb-6">{{ $col2Title }}</h4>
                    <ul class="space-y-4 text-[13px] text-gray-400 font-medium">
                        <li><a href="/contact" class="hover:text-white transition-colors">Contact & FAQ</a></li>
                        <li><a href="/shipping" class="hover:text-white transition-colors">Shipping & Delivery</a></li>
                        <li><a href="/returns" class="hover:text-white transition-colors">Returns & Warranty</a></li>
                    </ul>
                </div>

                <!-- Links 3 (Company) -->
                <div>
                    <h4 id="footer-col3-title-el" class="text-primary font-black text-xs tracking-widest uppercase mb-6">{{ $col3Title }}</h4>
                    <ul id="footer-company-links-list" class="space-y-4 text-[13px] text-gray-400 font-medium">
                        <li><a href="/about" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="/careers" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="/affiliates" class="hover:text-white transition-colors">Affiliates</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom -->
            <div data-preview-block="panel-footer-social" class="border-t border-white/10 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p id="footer-copyright-el" class="text-gray-600 text-xs">
                    &copy; {{ date('Y') }} {{ $storefrontName }}. {{ $footerCopyright ?: 'All rights reserved.' }}
                </p>
                <div class="flex items-center gap-4 text-gray-400">
                    <a id="footer-social-instagram" href="{{ $socialInstagram }}" class="hover:text-primary transition-colors"><i class="ph ph-instagram-logo text-xl"></i></a>
                    <a id="footer-social-twitter" href="{{ $socialTwitter }}" class="hover:text-primary transition-colors"><i class="ph ph-twitter-logo text-xl"></i></a>
                    <a id="footer-social-facebook" href="{{ $socialFacebook }}" class="hover:text-primary transition-colors"><i class="ph ph-facebook-logo text-xl"></i></a>
                    <a id="footer-social-youtube" href="{{ $socialYoutube }}" class="hover:text-primary transition-colors"><i class="ph ph-youtube-logo text-xl"></i></a>
                </div>
            </div>
        </div>
    </footer>
