const fs = require('fs');
const path = require('path');

const files = [
    path.join(__dirname, 'resources', 'views', 'auth', 'login.blade.php'),
    path.join(__dirname, 'resources', 'views', 'auth', 'social-password.blade.php')
];

const headReplacementTop = `@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    if (!$storefrontCompany) {
        $storefrontCompany = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \\App\\Models\\Company::first();
    }

    $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
    $publishedLayout = $storefrontCompany 
        ? ($isPreview ? \\Modules\\Ecommerce\\Models\\StorefrontLayout::editableFor($storefrontCompany) : \\Modules\\Ecommerce\\Models\\StorefrontLayout::publishedFor($storefrontCompany))
        : [];

    $layout = empty($layout) ? $publishedLayout : $layout;

    $storefrontName = $storefrontCompany?->company_name ?: ($layout['brand_name'] ?? 'Nexora Store');
    $store = $storefrontCompany?->ecommerce_slug ?: 'store';
    $logoUrl = !empty($layout['logo_path']) 
        ? (str_starts_with($layout['logo_path'], 'Modules/') ? Vite::asset($layout['logo_path']) : asset('storage/'.$layout['logo_path'])) 
        : ($storefrontCompany?->logoUrl() ?: asset('ecommerce/Nexora_Logo.png'));

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
<!DOCTYPE html>`;

const ambientTarget = `        /* Ambient Radial Light Blurs */
        .ambient-light-1 {
            position: fixed;
            top: -20%;
            left: -20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, rgba(255, 107, 0, 0.35) 0%, rgba(255, 107, 0, 0) 65%);
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
            background: radial-gradient(circle, rgba(153, 0, 0, 0.4) 0%, rgba(153, 0, 0, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }`;

const ambientReplacement = `        /* Ambient Radial Light Blurs */
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
            background: radial-gradient(circle, rgba({{ $accentR }}, {{ $accentG }}, {{ $accentB }}, 0.3) 0%, rgba({{ $accentR }}, {{ $accentG }}, {{ $accentB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }`;

const preloaderTarget = `    <!-- Preloader -->
    <script>
        if (!sessionStorage.getItem('techforge_visited')) {
            document.write(\`
                <div id="preloader" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full animate-pulse"></div>
                        <div class="flex items-center relative z-10">
                            <img src="{{ Vite::asset('Modules/E-Commerce/Store/resources/img/Techforge_Logo.png') }}" alt="TechForge Logo" class="h-20 w-auto object-contain animate-spin-fast drop-shadow-[0_0_25px_rgba(255,107,0,0.6)]">
                            <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-slide-text">TECHFORGE</span>
                        </div>
                    </div>
                </div>
            \`);
        }
    </script>`;

const preloaderReplacement = `    <!-- Preloader -->
    <script>
        if (!sessionStorage.getItem('storefront_visited')) {
            document.write(\`
                <div id="preloader" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full animate-pulse"></div>
                        <div class="flex items-center relative z-10">
                            <img src="{{ $logoUrl }}" alt="{{ $layout['brand_name'] ?? $storefrontName }} Logo" class="h-20 w-auto object-contain animate-spin-fast drop-shadow-[0_0_25px_rgba({{ $primaryR }},{{ $primaryG }},{{ $primaryB }},0.6)]">
                            <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-slide-text uppercase">{{ $layout['brand_name'] ?? $storefrontName }}</span>
                        </div>
                    </div>
                </div>
            \`);
        }
    </script>`;

files.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');

        // Normalize newlines for reliable replacement
        content = content.replace(/\\r\\n/g, '\\n');
        
        // 1. Replace head
        if (!content.includes('@php')) {
            content = content.replace('<!DOCTYPE html>', headReplacementTop);
        }

        // 2. Replace title logic
        content = content.replace(/<title>\{\{ config\('app\.name', 'TechForge'\) \}\} \| ([^<]+)<\/title>/g, "<title>{{ $layout['brand_name'] ?? $storefrontName }} | $1</title>");

        // 3. Replace Tailwind Config primary color
        content = content.replace(/primary: \{ DEFAULT: '#ff6b00', hover: '#e56000', glow: 'rgba\(255, 107, 0, 0\.5\)' \}/g, "primary: { DEFAULT: '{{ $primaryHex }}', hover: '{{ $primaryHex }}CC', glow: 'rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.5)' },\\n                        accent: '{{ $accentHex }}'");

        // 4. Replace Ambient Lights
        let normalAmbientTarget = ambientTarget.replace(/\\r\\n/g, '\\n');
        if (content.includes(normalAmbientTarget)) {
            content = content.replace(normalAmbientTarget, ambientReplacement.replace(/\\r\\n/g, '\\n'));
        }

        // 5. Replace Preloader
        let normalPreloaderTarget = preloaderTarget.replace(/\\r\\n/g, '\\n');
        if (content.includes(normalPreloaderTarget)) {
            content = content.replace(normalPreloaderTarget, preloaderReplacement.replace(/\\r\\n/g, '\\n'));
        }

        // 6. Replace Hardcoded Logo and Name in HTML body
        content = content.replace(/<img src="\{\{ Vite::asset\('Modules\/E-Commerce\/Store\/resources\/img\/Techforge_Logo\.png'\) \}\}" alt="TechForge Logo" class="h-7 w-auto object-contain">/g, '<img src="{{ $logoUrl }}" alt="{{ $layout[\'brand_name\'] ?? $storefrontName }} Logo" class="h-7 w-auto object-contain">');
        
        content = content.replace(/Sign in to continue to TechForge/g, "Sign in to continue to {{ $layout['brand_name'] ?? $storefrontName }}");
        content = content.replace(/Join TechForge today/g, "Join {{ $layout['brand_name'] ?? $storefrontName }} today");
        
        content = content.replace(/techforge_visited/g, "storefront_visited");

        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated ' + path.basename(file));
    }
});
