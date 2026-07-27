@php
    $admin = auth('ecommerce_admin')->user();
    $company = $admin?->getCompany();
    $companyName = $company?->company_name ?? 'Store';
    $slug = $company?->ecommerce_slug;
    $storeUrl = $slug ? '//' . $slug . '.' . config('ecommerce.storefront_base_domain') : null;
    $adminName = trim(($admin?->first_name ?? '') . ' ' . ($admin?->last_name ?? '')) ?: $companyName;
    $adminEmail = $admin?->email ?? $admin?->company_email ?? '';
    $initials = strtoupper(substr($adminName, 0, 2));

    // Determine breadcrumb based on current route
    $routeName = request()->route()?->getName() ?? '';
    $breadcrumbs = [];
    $breadcrumbs[] = ['label' => 'Home', 'url' => route('ecommerce.admin.dashboard')];

    if (str_starts_with($routeName, 'ecommerce.admin.crm')) {
        // CRM sub-routes
        $crmDashboard = route('ecommerce.admin.crm.dashboard');
        $breadcrumbs[] = ['label' => 'CRM', 'url' => $crmDashboard];

        if (str_starts_with($routeName, 'ecommerce.admin.crm.customers')) {
            $breadcrumbs[] = ['label' => 'Customers', 'url' => route('ecommerce.admin.crm.customers')];
            if (in_array($routeName, ['ecommerce.admin.crm.customers.show'])) {
                // We can't easily get the name here without a DB query — show a generic label
                $breadcrumbs[] = ['label' => 'Customer Detail', 'url' => null];
            } else {
                $breadcrumbs[] = ['label' => 'All Customers', 'url' => null];
            }
        } elseif (str_starts_with($routeName, 'ecommerce.admin.crm.leads')) {
            $breadcrumbs[] = ['label' => 'Sales Pipeline', 'url' => route('ecommerce.admin.crm.leads.pipeline')];
            if (in_array($routeName, ['ecommerce.admin.crm.leads.create', 'ecommerce.admin.crm.leads.store'])) {
                $breadcrumbs[] = ['label' => 'New Lead', 'url' => null];
            } elseif (in_array($routeName, ['ecommerce.admin.crm.leads.edit', 'ecommerce.admin.crm.leads.update'])) {
                $breadcrumbs[] = ['label' => 'Edit Lead', 'url' => null];
            } elseif ($routeName === 'ecommerce.admin.crm.leads.show') {
                $breadcrumbs[] = ['label' => 'Lead Detail', 'url' => null];
            } else {
                $breadcrumbs[] = ['label' => 'Pipeline', 'url' => null];
            }
        } elseif (str_starts_with($routeName, 'ecommerce.admin.crm.coupons')) {
            $breadcrumbs[] = ['label' => 'Coupons', 'url' => route('ecommerce.admin.crm.coupons')];
            if (in_array($routeName, ['ecommerce.admin.crm.coupons.create', 'ecommerce.admin.crm.coupons.store'])) {
                $breadcrumbs[] = ['label' => 'New Coupon', 'url' => null];
            } elseif (in_array($routeName, ['ecommerce.admin.crm.coupons.edit', 'ecommerce.admin.crm.coupons.update'])) {
                $breadcrumbs[] = ['label' => 'Edit Coupon', 'url' => null];
            } else {
                $breadcrumbs[] = ['label' => 'All Coupons', 'url' => null];
            }
        } elseif (str_starts_with($routeName, 'ecommerce.admin.crm.abandoned-carts')) {
            $breadcrumbs[] = ['label' => 'Abandoned Carts', 'url' => null];
        } elseif (str_starts_with($routeName, 'ecommerce.admin.crm.reviews')) {
            $breadcrumbs[] = ['label' => 'Reviews', 'url' => null];
        } elseif (str_starts_with($routeName, 'ecommerce.admin.crm.templates')) {
            $breadcrumbs[] = ['label' => 'Templates', 'url' => null];
        } else {
            // CRM dashboard — just "CRM"
            $breadcrumbs[] = ['label' => 'Dashboard', 'url' => null];
        }
    } elseif (str_starts_with($routeName, 'ecommerce.admin.listings')) {
        if (in_array($routeName, ['ecommerce.admin.listings.create', 'ecommerce.admin.listings.store'])) {
            $breadcrumbs[] = ['label' => 'Products', 'url' => route('ecommerce.admin.listings')];
            $breadcrumbs[] = ['label' => 'Add Listing', 'url' => null];
        } elseif (in_array($routeName, ['ecommerce.admin.listings.edit', 'ecommerce.admin.listings.update'])) {
            $breadcrumbs[] = ['label' => 'Products', 'url' => route('ecommerce.admin.listings')];
            $breadcrumbs[] = ['label' => 'Edit Listing', 'url' => null];
        } else {
            $breadcrumbs[] = ['label' => 'Products', 'url' => null];
        }
    } elseif (str_starts_with($routeName, 'ecommerce.admin.orders')) {
        $breadcrumbs[] = ['label' => 'Orders', 'url' => null];
    } elseif (str_starts_with($routeName, 'ecommerce.admin.layout')) {
        $breadcrumbs[] = ['label' => 'Store Editor', 'url' => null];
    } elseif (str_starts_with($routeName, 'ecommerce.admin.dashboard')) {
        // Just "Home"
    } else {
        $breadcrumbs[] = ['label' => $heading ?? 'Admin', 'url' => null];
    }

    // Company logo
    $companyLogoUrl = $company?->logoUrl();

    // Check if this client's storefront is published
    $clientId = $company?->id;
    $hasPublishedLayout = $clientId
        ? \Modules\Ecommerce\Models\StorefrontLayout::where('client_id', $clientId)->whereNotNull('published_layout')->exists()
        : false;
@endphp

<style>
    /* ── Navbar ── */
    .admin-navbar {
        height: 56px;
        background: var(--c-header-bg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        position: sticky;
        top: 0;
        z-index: 50;
        gap: 16px;
    }

    .navbar-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .navbar-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
        flex-shrink: 0;
    }

    .navbar-logo img {
        height: 30px;
        object-fit: contain;
    }

    .company-logo {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .company-logo img {
        height: 26px;
        max-width: 100px;
        object-fit: contain;
    }

    .company-logo .no-logo {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
    }

    /* Logo Divider */
    .navbar-divider {
        width: 1px;
        height: 24px;
        background: rgba(255,255,255,0.15);
        flex-shrink: 0;
    }

    /* Breadcrumb */
    .navbar-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        overflow: hidden;
    }

    .navbar-breadcrumb a,
    .navbar-breadcrumb span {
        font-size: 13px;
        white-space: nowrap;
        text-decoration: none;
    }

    .navbar-breadcrumb a {
        color: rgba(255,255,255,0.55);
        transition: color 0.15s;
    }

    .navbar-breadcrumb a:hover {
        color: rgba(255,255,255,0.85);
    }

    .navbar-breadcrumb .sep {
        color: rgba(255,255,255,0.2);
        font-size: 12px;
        flex-shrink: 0;
    }

    .navbar-breadcrumb .current {
        color: #fff;
        font-weight: 600;
    }

    /* Store Status */
    .store-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        flex-shrink: 0;
        margin-left: 4px;
    }

    .store-status .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .store-status.live {
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
    }

    .store-status.live .dot { background: #22c55e; }

    .store-status.draft {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
    }

    .store-status.draft .dot { background: #f59e0b; }

    /* Right Side */
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .navbar-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 0;
        background: transparent;
        color: rgba(255,255,255,0.6);
        font-size: 18px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
        position: relative;
    }

    .navbar-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    .navbar-btn.primary {
        background: var(--c-primary);
        color: #fff;
        width: auto;
        padding: 0 12px;
        gap: 6px;
        font-size: 14px;
        font-weight: 600;
    }

    .navbar-btn.primary:hover {
        background: #1a5aa8;
    }

    .navbar-btn.primary i {
        font-size: 15px;
    }

    /* Decorative notification icon (non-interactive) */
    .navbar-icon-inert {
        cursor: default !important;
        opacity: 0.4;
    }

    .navbar-btn .badge-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #ef4444;
        border: 2px solid var(--c-header-bg);
    }

    /* User Menu */
    .user-menu-wrap {
        position: relative;
        margin-left: 2px;
    }

    .user-avatar {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: var(--c-primary);
        color: #fff;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: opacity 0.15s;
    }

    .user-avatar:hover {
        opacity: 0.85;
    }

    .user-dropdown {
        visibility: hidden;
        position: absolute;
        z-index: 20;
        top: 40px;
        right: 0;
        width: 240px;
        overflow: hidden;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        opacity: 0;
        transform: translateY(-6px);
        transition: all 0.16s ease;
        border: 1px solid var(--c-border);
    }

    .user-menu-wrap[data-open="true"] .user-dropdown {
        visibility: visible;
        opacity: 1;
        transform: translateY(0);
    }

    .user-dropdown-header {
        padding: 16px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .user-dropdown-header .ud-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
    }

    .user-dropdown-header .ud-email {
        font-size: 12px;
        color: var(--c-text-muted);
        margin-top: 2px;
    }

    .user-dropdown-header .ud-company {
        font-size: 11px;
        color: var(--c-primary);
        font-weight: 600;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .user-dropdown .ud-link {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 11px 16px;
        border: 0;
        background: #fff;
        color: var(--c-text);
        font: 500 13px Inter, Arial, sans-serif;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.1s;
    }

    .user-dropdown .ud-link:hover {
        background: #f5f5f5;
    }

    .user-dropdown .ud-link i {
        font-size: 16px;
        color: var(--c-text-muted);
        width: 18px;
        text-align: center;
    }

    .user-dropdown .ud-link.storefront-link i {
        color: var(--c-primary);
    }

    .user-dropdown .ud-divider {
        height: 1px;
        background: var(--c-border);
        margin: 0;
    }

    .user-dropdown .ud-link.logout {
        color: #dc2626;
    }

    .user-dropdown .ud-link.logout i {
        color: #dc2626;
    }

    .user-dropdown .ud-link.logout:hover {
        background: #fef2f2;
    }
</style>

<header class="admin-navbar">
    <!-- Left: Logo + Breadcrumb -->
    <div class="navbar-left">
        <a class="navbar-logo" href="{{ route('ecommerce.admin.dashboard') }}" title="Dashboard">
            <img src="{{ asset('images/Banner Transparent.png') }}" style="filter: brightness(0) invert(1);" alt="Nexora Logo">
        </a>

        <div class="company-logo">
            @if($companyLogoUrl)
                <img src="{{ $companyLogoUrl }}" alt="{{ $companyName }} logo">
            @else
                <span class="no-logo">{{ $companyName }}</span>
            @endif
        </div>

        @if(count($breadcrumbs) > 1 || ($hasPublishedLayout !== null))
            <span class="navbar-divider"></span>

            <div class="navbar-breadcrumb">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="sep"><i class="ph ph-caret-right"></i></span>
                    @endif
                    @if($crumb['url'])
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span class="current">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </div>

            <span class="store-status {{ $hasPublishedLayout ? 'live' : 'draft' }}">
                <span class="dot"></span>
                {{ $hasPublishedLayout ? 'Published' : 'Draft' }}
            </span>
        @endif
    </div>

    <!-- Right: Actions -->
    <div class="navbar-right">
        <a class="navbar-btn" href="{{ route('ecommerce.admin.listings.create') }}" title="Add listing">
            <i class="ph ph-plus"></i>
        </a>

        @if($storeUrl)
            <a class="navbar-btn" href="{{ $storeUrl }}" target="_blank" rel="noopener" title="Open storefront">
                <i class="ph ph-arrow-square-out"></i>
            </a>
        @endif

        <!-- Live Chat -->
        @include('ecommerce::components.admin-chat-widget')

        <!-- Notifications Bell -->
        @include('ecommerce::components.admin-notification-bell')

        <!-- User Menu -->
        <div class="user-menu-wrap" data-user-menu>
            <button type="button" class="user-avatar" data-user-menu-button aria-label="Open user menu" aria-expanded="false">
                {{ $initials }}
            </button>
            <div class="user-dropdown" data-user-menu-dropdown>
                <div class="user-dropdown-header">
                    <div class="ud-name">{{ $adminName }}</div>
                    @if($adminEmail)
                        <div class="ud-email">{{ $adminEmail }}</div>
                    @endif
                    <div class="ud-company">
                        <i class="ph ph-storefront"></i> {{ $companyName }}
                    </div>
                </div>

                @if($storeUrl)
                    <a class="ud-link storefront-link" href="{{ $storeUrl }}" target="_blank" rel="noopener">
                        <i class="ph ph-arrow-square-out"></i> Open Storefront
                    </a>
                @endif

                <a class="ud-link" href="#" onclick="alert('Settings coming soon!'); return false;">
                    <i class="ph ph-gear"></i> Settings
                </a>

                <hr class="ud-divider">

                <form method="post" action="{{ route('ecommerce.admin.logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="ud-link logout">
                        <i class="ph ph-sign-out"></i> Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-user-menu]').forEach(function(menu) {
            var button = menu.querySelector('[data-user-menu-button]');
            if (!button) return;

            button.addEventListener('click', function(event) {
                event.stopPropagation();
                var open = menu.getAttribute('data-open') !== 'true';
                menu.setAttribute('data-open', open ? 'true' : 'false');
                button.setAttribute('aria-expanded', String(open));
            });

            document.addEventListener('click', function() {
                menu.setAttribute('data-open', 'false');
                button.setAttribute('aria-expanded', 'false');
            });
        });
    });
</script>
