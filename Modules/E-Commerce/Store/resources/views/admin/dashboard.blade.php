@php
    $admin = auth('ecommerce_admin')->user();
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';
    $companyName = $storefrontCompany?->company_name ?? ($admin?->getCompany()?->company_name ?? 'Store');
    $adminName = trim(($admin?->first_name ?? '') . ' ' . ($admin?->last_name ?? '')) ?: 'Admin';
    $phTime = now()->timezone('Asia/Manila');
    $phHour = (int) $phTime->format('H');
    $greeting = $phHour < 12 ? 'morning' : ($phHour < 18 ? 'afternoon' : 'evening');
    $hasOrders = $orderCount > 0;
    $hasListings = $listingCount > 0;
    $newestListing = $recentListings->first();
    $newestOrder = $recentOrders->first();
@endphp

@extends('ecommerce::admin.layout', ['title' => 'E-commerce Overview'])

@section('content')
<style>
    /* Welcome Section */
    .welcome-section {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 24px;
    }

    .welcome-text h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--c-text);
        margin-bottom: 6px;
    }

    .welcome-text h1 span {
        color: var(--c-primary);
    }

    .welcome-text .subtitle {
        font-size: 15px;
        color: var(--c-text-muted);
        line-height: 1.5;
    }

    .welcome-text .store-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        padding: 5px 12px;
        border-radius: 20px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
    }

    .welcome-text .store-badge i {
        font-size: 14px;
    }

    .welcome-quick-date {
        text-align: right;
        flex-shrink: 0;
    }

    .welcome-quick-date .date {
        font-size: 15px;
        font-weight: 600;
        color: var(--c-text);
    }

    .welcome-quick-date .time {
        font-size: 13px;
        color: var(--c-text-muted);
        margin-top: 2px;
    }

    /* Stats Bar */
    .dash-stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }

    .dash-stat-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        transition: box-shadow 0.15s, transform 0.1s;
    }

    .dash-stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        transform: translateY(-1px);
    }

    .dash-stat-card .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .dash-stat-card .stat-icon.bom { background: #f5f3ff; color: #7c3aed; }
    .dash-stat-card .stat-icon.listings { background: #eff6ff; color: #2563eb; }
    .dash-stat-card .stat-icon.active-listings { background: #f0fdf4; color: #16a34a; }
    .dash-stat-card .stat-icon.orders { background: #fffbeb; color: #d97706; }

    .dash-stat-card .stat-info .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }

    .dash-stat-card .stat-info .stat-label {
        font-size: 13px;
        color: var(--c-text-muted);
        font-weight: 500;
    }

    /* Quick Action Cards */
    .action-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 32px;
    }

    .action-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }

    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    .action-card .action-bg-icon {
        position: absolute;
        right: -8px;
        bottom: -8px;
        font-size: 72px;
        opacity: 0.04;
        color: var(--c-text);
        pointer-events: none;
    }

    .action-card .action-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 16px;
    }

    .action-card .action-icon-wrap.blue { background: #eff6ff; color: #2563eb; }
    .action-card .action-icon-wrap.green { background: #f0fdf4; color: #16a34a; }
    .action-card .action-icon-wrap.purple { background: #f5f3ff; color: #7c3aed; }
    .action-card .action-icon-wrap.amber { background: #fffbeb; color: #d97706; }

    .action-card h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 6px;
    }

    .action-card p {
        font-size: 13px;
        color: var(--c-text-muted);
        line-height: 1.5;
        flex: 1;
    }

    .action-card .action-footer {
        margin-top: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--c-primary);
    }

    .action-card .action-footer i {
        font-size: 14px;
        transition: transform 0.15s;
    }

    .action-card:hover .action-footer i {
        transform: translateX(3px);
    }

    /* Activity Bar */
    .activity-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        background: #fafbfc;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        margin-bottom: 24px;
        font-size: 14px;
        color: var(--c-text-muted);
    }

    .activity-bar .activity-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        flex-shrink: 0;
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .activity-bar strong {
        color: var(--c-text);
        font-weight: 600;
    }

    .activity-bar a {
        color: var(--c-primary);
        text-decoration: none;
        font-weight: 600;
    }

    .activity-bar a:hover {
        text-decoration: underline;
    }

    /* Dual Table Layout */
    .tables-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .dash-table-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
    }

    .dash-table-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .dash-table-card .card-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .dash-table-card .card-header h3 i {
        font-size: 16px;
        color: var(--c-text-muted);
    }

    .dash-table-card .card-header a {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .dash-table-card .card-header a:hover {
        text-decoration: underline;
    }

    .dash-table-card .card-header a i {
        font-size: 14px;
    }

    .dash-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dash-table thead th {
        padding: 10px 16px;
        font-size: 11px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .dash-table tbody tr {
        transition: background 0.1s;
    }

    .dash-table tbody tr:hover {
        background: #f8fafc;
    }

    .dash-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 13px;
        color: var(--c-text);
        vertical-align: middle;
    }

    .dash-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dash-table .product-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dash-table .product-thumb {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid var(--c-border);
        flex-shrink: 0;
        background: #f9fafb;
    }

    .dash-table .product-thumb-placeholder {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 14px;
        flex-shrink: 0;
        border: 1px solid var(--c-border);
    }

    .dash-table .product-name {
        font-weight: 500;
        color: var(--c-text);
    }

    .dash-table .status-badge-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .dash-table .status-badge-sm .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .dash-table .status-badge-sm.active { background: #f0fdf4; color: #16a34a; }
    .dash-table .status-badge-sm.active .dot { background: #22c55e; }
    .dash-table .status-badge-sm.draft { background: #f9fafb; color: #6b7280; }
    .dash-table .status-badge-sm.draft .dot { background: #9ca3af; }
    .dash-table .status-badge-sm.archived { background: #fef2f2; color: #dc2626; }
    .dash-table .status-badge-sm.archived .dot { background: #ef4444; }

    .dash-table .order-status-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .dash-table .order-status-sm .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .dash-table .order-status-sm.pending { background: #fffbeb; color: #d97706; }
    .dash-table .order-status-sm.pending .dot { background: #f59e0b; }
    .dash-table .order-status-sm.processing { background: #eff6ff; color: #2563eb; }
    .dash-table .order-status-sm.processing .dot { background: #3b82f6; }
    .dash-table .order-status-sm.shipped { background: #f5f3ff; color: #7c3aed; }
    .dash-table .order-status-sm.shipped .dot { background: #8b5cf6; }
    .dash-table .order-status-sm.delivered { background: #f0fdf4; color: #16a34a; }
    .dash-table .order-status-sm.delivered .dot { background: #22c55e; }
    .dash-table .order-status-sm.cancelled { background: #fef2f2; color: #dc2626; }
    .dash-table .order-status-sm.cancelled .dot { background: #ef4444; }

    .dash-table .price-sm {
        font-weight: 700;
        color: var(--c-text);
    }

    .dash-table .muted-text {
        color: var(--c-text-muted);
    }

    .dash-table .empty-row td {
        text-align: center;
        padding: 32px 16px;
        color: var(--c-text-muted);
        font-size: 13px;
    }

    /* Empty State Placeholder */
    .dash-empty-state {
        text-align: center;
        padding: 48px 24px;
    }

    .dash-empty-state .empty-icon {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 12px;
        display: block;
    }

    .dash-empty-state h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 6px;
    }

    .dash-empty-state p {
        font-size: 13px;
        color: var(--c-text-muted);
        line-height: 1.5;
    }

    @media (max-width: 1100px) {
        .tables-grid { grid-template-columns: 1fr; }
        .dash-stats-bar { grid-template-columns: repeat(2, 1fr); }
        .action-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Welcome Section -->
<div class="welcome-section">
    <div class="welcome-text">
        <h1>Good {{ $greeting }}, <span>{{ $adminName }}</span></h1>
        <div class="subtitle">
            @if(!$hasListings && !$hasOrders)
                Your store is ready. Add your first product to get started.
            @elseif($hasListings && !$hasOrders)
                Your products are live — now let's get those first orders in.
            @else
                You have <strong>{{ $activeListingCount }} active listing{{ $activeListingCount !== 1 ? 's' : '' }}</strong> and <strong>{{ $orderCount }} order{{ $orderCount !== 1 ? 's' : '' }}</strong> to manage.
            @endif
        </div>
        <div class="store-badge">
            <i class="ph ph-storefront"></i> {{ $companyName }} &middot; {{ $store }}.{{ config('ecommerce.storefront_base_domain') }}
        </div>
    </div>
    <div class="welcome-quick-date">
        <div class="date">{{ $phTime->format('F j, Y') }}</div>
        <div class="time">{{ $phTime->format('l') }} &middot; {{ $phTime->format('g:i A') }} PHT</div>
    </div>
</div>

<!-- Stats Bar (clickable) -->
<div class="dash-stats-bar">
    <div class="dash-stat-card">
        <div class="stat-icon bom"><i class="ph ph-flask"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $bomCount }}</div>
            <div class="stat-label">Active BOMs</div>
        </div>
    </div>
    <a class="dash-stat-card" href="{{ route('ecommerce.admin.listings') }}">
        <div class="stat-icon listings"><i class="ph ph-tag"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $listingCount }}</div>
            <div class="stat-label">Total Listings</div>
        </div>
    </a>
    <a class="dash-stat-card" href="{{ route('ecommerce.admin.listings') }}">
        <div class="stat-icon active-listings"><i class="ph ph-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $activeListingCount }}</div>
            <div class="stat-label">Active</div>
        </div>
    </a>
    <a class="dash-stat-card" href="{{ route('ecommerce.admin.orders') }}">
        <div class="stat-icon orders"><i class="ph ph-shopping-cart"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $orderCount }}</div>
            <div class="stat-label">Total Orders</div>
        </div>
    </a>
</div>

<!-- Quick Action Cards -->
<div class="action-grid">
    <a class="action-card" href="{{ route('ecommerce.admin.listings.create') }}">
        <i class="ph ph-boxes action-bg-icon"></i>
        <div class="action-icon-wrap blue"><i class="ph ph-plus-circle"></i></div>
        <h3>Add a product</h3>
        <p>Create a new storefront listing with a title, price, and photo. Attach a Manufacturing-approved BOM.</p>
        <div class="action-footer">
            Add product <i class="ph ph-arrow-right"></i>
        </div>
    </a>
    <a class="action-card" href="{{ route('ecommerce.admin.layout.edit') }}">
        <i class="ph ph-paint-brush action-bg-icon"></i>
        <div class="action-icon-wrap green"><i class="ph ph-palette"></i></div>
        <h3>Customize design</h3>
        <p>Pick a theme, set your brand colors, and customize your storefront layout to match your brand.</p>
        <div class="action-footer">
            Open store editor <i class="ph ph-arrow-right"></i>
        </div>
    </a>
    <a class="action-card" href="{{ route('ecommerce.admin.orders') }}">
        <i class="ph ph-shopping-cart action-bg-icon"></i>
        <div class="action-icon-wrap amber"><i class="ph ph-clipboard-text"></i></div>
        <h3>View orders</h3>
        <p>Manage incoming orders, update shipping status, and track fulfillment from one place.</p>
        <div class="action-footer">
            Go to orders <i class="ph ph-arrow-right"></i>
        </div>
    </a>
</div>

<!-- Activity Bar -->
<div class="activity-bar">
    <span class="activity-dot"></span>
    @if($newestOrder)
        <span>Last order placed <strong>{{ $newestOrder->created_at->diffForHumans() }}</strong>
        @if($newestListing)
            &middot; newest listing added <strong>{{ $newestListing->created_at->diffForHumans() }}</strong>
        @endif
        </span>
        <span style="margin-left: auto; font-size: 13px;">
            <a href="{{ route('ecommerce.admin.orders') }}">View all orders <i class="ph ph-arrow-right" style="font-size: 12px;"></i></a>
        </span>
    @elseif($newestListing)
        <span>Newest listing added <strong>{{ $newestListing->created_at->diffForHumans() }}</strong></span>
        <span style="margin-left: auto; font-size: 13px;">
            <a href="{{ route('ecommerce.admin.listings.create') }}">Add another <i class="ph ph-arrow-right" style="font-size: 12px;"></i></a>
        </span>
    @else
        <span>Your store is ready — add your first product to get started.</span>
    @endif
</div>

<!-- Tables Grid: Recent Listings + Recent Orders -->
<div class="tables-grid">
    <!-- Recent Listings -->
    <div class="dash-table-card">
        <div class="card-header">
            <h3><i class="ph ph-tag"></i> Recent Listings</h3>
            <a href="{{ route('ecommerce.admin.listings') }}">View all <i class="ph ph-arrow-right"></i></a>
        </div>
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Qty</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentListings as $listing)
                    <tr>
                        <td>
                            <div class="product-cell">
                                @if($listing->image_url)
                                    <img class="product-thumb" src="{{ asset('storage/' . $listing->image_url) }}" alt="">
                                @else
                                    <div class="product-thumb-placeholder"><i class="ph ph-box"></i></div>
                                @endif
                                <span class="product-name">{{ $listing->name ?: 'Item #'.$listing->inventory_item_id }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge-sm {{ $listing->status }}">
                                <span class="dot"></span> {{ ucfirst($listing->status) }}
                            </span>
                        </td>
                        <td class="muted-text">{{ $listing->available_quantity }}</td>
                        <td class="price-sm">&#8369;{{ number_format((float) ($listing->price ?: 0), 2) }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="4">No listings yet. <a href="{{ route('ecommerce.admin.listings.create') }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none;">Add your first product</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Recent Orders -->
    <div class="dash-table-card">
        <div class="card-header">
            <h3><i class="ph ph-shopping-cart"></i> Recent Orders</h3>
            <a href="{{ route('ecommerce.admin.orders') }}">View all <i class="ph ph-arrow-right"></i></a>
        </div>
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    <tr>
                        <td>
                            <span class="product-name" style="font-family: 'SF Mono', monospace; font-size: 12px;">#{{ substr($order->id, 0, 8) }}</span>
                            <span class="muted-text" style="font-size: 11px; display: block;">
                                {{ $order->created_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <span class="order-status-sm {{ $order->status }}">
                                <span class="dot"></span> {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="muted-text">
                            @php $itemCount = $order->items->sum('quantity'); @endphp
                            {{ $itemCount }} item{{ $itemCount !== 1 ? 's' : '' }}
                        </td>
                        <td class="price-sm">&#8369;{{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="4">No orders yet. Orders will appear here once customers start purchasing.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
