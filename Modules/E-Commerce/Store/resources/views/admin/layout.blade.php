<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Nexora E-commerce')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    <!-- Load Phosphor Icons for the sidebar -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @yield('head')
    <style>
        :root {
            --c-sidebar-bg: #0B1E3D;
            --c-sidebar-hover: #132B52;
            --c-sidebar-text: #FFFFFF;
            --c-sidebar-text-muted: #7BBEF0;
            --c-sidebar-active-bg: #1B6FC8;
            --c-sidebar-active-text: #FFFFFF;
            --c-header-bg: #132B52;
            --c-bg: #F4F6FA;
            --c-text: #0B1E3D;
            --c-text-muted: #5B7A9D;
            --c-border: #E2E8F0;
            --c-primary: #1B6FC8;
            --c-primary-hover: #1B3A6B;
            font-family: Inter, Arial, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--c-bg); color: var(--c-text); display: flex; flex-direction: column; min-height: 100vh; }

        /* Top header styles moved to components/admin-navbar.blade.php */

        /* Layout Structure */
        .layout-wrapper { display: flex; flex: 1; overflow: hidden; }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: var(--c-sidebar-bg);
            border-right: none;
            display: flex;
            flex-direction: column;
            padding: 16px 12px;
        }
        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .sidebar-link {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 12px; border-radius: 6px;
            color: var(--c-sidebar-text); text-decoration: none;
            font-size: 14px; font-weight: 500; transition: background 0.1s;
        }
        .sidebar-link:hover { background: var(--c-sidebar-hover); }
        .sidebar-link.active { background: var(--c-sidebar-active-bg); color: var(--c-sidebar-active-text); font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .sidebar-link i { font-size: 18px; color: var(--c-sidebar-text-muted); }
        .sidebar-link.active i { color: var(--c-sidebar-active-text); }
        .sidebar-section-title {
            font-size: 12px; font-weight: 600; color: var(--c-sidebar-text-muted);
            margin: 16px 0 8px 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* Main Area */
        .main-area { flex: 1; overflow-y: auto; padding: 32px 48px; background: var(--c-bg); }

        /* Global Styles inside Main Area */
        .page-heading { margin-bottom: 32px; }
        h1 { font-size: 24px; font-weight: 600; color: var(--c-text); }
        .company-subtitle { color: var(--c-text-muted); font-size: 14px; margin-top: 4px; }

        .card { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--c-border); text-align: left; }
        th { color: var(--c-text-muted); font-size: 13px; font-weight: 500; }
        td { font-size: 14px; }

        button, .button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 6px; padding: 8px 16px; background: var(--c-text); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; transition: background 0.15s; }
        .button:hover, button:hover { background: #333; }
        .button.alt { background: #fff; color: var(--c-text); border: 1px solid #ccc; }
        .button.alt:hover { background: #f5f5f5; border-color: #999; }

        input, textarea, select { width: 100%; margin-top: 6px; border: 1px solid #ccc; border-radius: 6px; padding: 10px 12px; color: var(--c-text); font: inherit; font-size: 14px; transition: border-color 0.15s; }
        input:focus, textarea:focus, select:focus { border-color: var(--c-primary); outline: none; box-shadow: 0 0 0 2px rgba(29, 78, 137, 0.1); }
        label { display: block; margin-top: 16px; color: var(--c-text); font-size: 14px; font-weight: 500; }

        .hint { margin: 6px 0 0; color: var(--c-text-muted); font-size: 13px; }
        .success { margin-bottom: 24px; border-radius: 8px; padding: 14px; background: #DCFCE7; color: #16A34A; border: 1px solid #BBF7D0; font-size: 14px; display: flex; align-items: center; gap: 8px;}
        .error { color: #DC2626; font-size: 13px; margin-top: 4px;}

        /* Layout Editor Specifics overriding */
        .editor-grid { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(320px, .8fr); gap: 24px; align-items: start; }
        .section-card { margin-top: 16px; padding: 20px; border: 1px solid var(--c-border); border-radius: 10px; background: #fafafa; }
        .section-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
        .section-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .toggle { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; cursor: pointer; }
        .toggle input { width: auto; margin: 0; accent-color: var(--c-primary); transform: scale(1.1); }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
        .publish-note { border-left: 3px solid var(--c-primary); background: #f0f4f8; padding: 14px; font-size: 14px; margin-bottom: 20px; border-radius: 0 8px 8px 0; }

        @media (max-width: 900px) {
            .editor-grid { grid-template-columns: 1fr; }
            .sidebar { display: none; }

            .main-area { padding: 20px; }
        }
    </style>
</head>
<body>
    @if(!($hideLayout ?? false))
        @include('ecommerce::components.admin-navbar')
    @endif

    <div class="layout-wrapper">
        @if(!($hideLayout ?? false))
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.dashboard') ? 'active' : '' }}" href="{{ route('ecommerce.admin.dashboard') }}">
                    <i class="ph ph-house"></i> Home
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.orders') ? 'active' : '' }}" href="{{ route('ecommerce.admin.orders') }}">
                    <i class="ph ph-shopping-cart"></i> Orders
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.listings*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.listings') }}">
                    <i class="ph ph-tag"></i> Products
                </a>

                <div class="sidebar-section-title">Sales Channels</div>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.layout.*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.layout.edit') }}">
                    <i class="ph ph-storefront"></i> {{ auth('ecommerce_admin')->user()?->getCompany()?->company_name ?? 'Online Store' }}
                </a>

                <div class="sidebar-section-title">CRM</div>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.dashboard') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.dashboard') }}">
                    <i class="ph ph-gauge"></i> Dashboard
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.customers*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.customers') }}">
                    <i class="ph ph-users"></i> Customers
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.abandoned-carts') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.abandoned-carts') }}">
                    <i class="ph ph-shopping-cart"></i> Abandoned Carts
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.leads*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.leads.pipeline') }}">
                    <i class="ph ph-funnel"></i> Sales Pipeline
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.reviews') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.reviews') }}">
                    <i class="ph ph-star"></i> Reviews
                </a>
                <a class="sidebar-link {{ request()->routeIs('ecommerce.admin.crm.coupons*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.crm.coupons') }}">
                    <i class="ph ph-tag"></i> Coupons
                </a>
            </nav>

            <nav style="margin-top: auto;">
                <a class="sidebar-link" href="#" onclick="alert('Settings coming soon!'); return false;">
                    <i class="ph ph-gear"></i> Settings
                </a>
            </nav>
        </aside>
        @endif

        <main class="main-area" style="{{ ($hideLayout ?? false) ? 'padding: 0;' : '' }}">
            @if(request()->routeIs('ecommerce.admin.dashboard'))
                <!-- Dashboard handles its own header -->
            @else
                <div class="page-heading">
                    <h1>{{ $heading ?? 'E-commerce Admin' }}</h1>
                </div>
            @endif

            @if (session('success'))
                <div class="success"><i class="ph ph-check-circle" style="font-size: 18px;"></i> {{ session('success') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- User menu JS is in components/admin-navbar.blade.php -->
</body>
</html>
