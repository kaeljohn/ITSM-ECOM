@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';

    // Lightweight aggregate queries — no full table scan
    $globalTotal = \Modules\Ecommerce\Models\Order::count();
    $globalPending = \Modules\Ecommerce\Models\Order::where('status', 'pending')->count();
    $globalProcessing = \Modules\Ecommerce\Models\Order::where('status', 'processing')->count();
    $globalCompleted = \Modules\Ecommerce\Models\Order::whereIn('status', ['shipped', 'delivered'])->count();
    $globalRevenue = \Modules\Ecommerce\Models\Order::whereIn('status', ['delivered', 'shipped', 'processing'])->sum('total');
@endphp

@extends('ecommerce::admin.layout', ['title' => 'Storefront Orders', 'heading' => 'Storefront Orders'])

@section('content')
<style>
    /* Stats Bar */
    .order-stats-bar {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .order-stat-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .order-stat-card .stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .order-stat-card .stat-icon.total { background: #eff6ff; color: #2563eb; }
    .order-stat-card .stat-icon.pending { background: #fffbeb; color: #d97706; }
    .order-stat-card .stat-icon.processing { background: #eff6ff; color: #2563eb; }
    .order-stat-card .stat-icon.completed { background: #f0fdf4; color: #16a34a; }
    .order-stat-card .stat-icon.cancelled { background: #fef2f2; color: #dc2626; }
    .order-stat-card .stat-icon.revenue { background: #f5f3ff; color: #7c3aed; }

    .order-stat-card .stat-info .stat-number {
        font-size: 22px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }

    .order-stat-card .stat-info .stat-label {
        font-size: 12px;
        color: var(--c-text-muted);
        font-weight: 500;
    }

    /* Toolbar */
    .orders-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: nowrap;
    }

    .orders-search-wrapper {
        position: relative;
        flex: 1;
        min-width: 0;
    }

    .orders-search-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
        pointer-events: none;
    }

    .orders-search-wrapper input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: var(--c-text);
        background: #fff;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-sizing: border-box;
    }

    .orders-search-wrapper input:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .orders-search-wrapper input::placeholder {
        color: #9ca3af;
        opacity: 1;
    }

    .orders-filter-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .orders-filter-wrapper::after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #6b7280;
        pointer-events: none;
    }

    .orders-filter-select {
        padding: 8px 30px 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 13px;
        color: var(--c-text);
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s;
        min-width: 120px;
        max-width: 160px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .orders-filter-select:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    /* Orders Table */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table thead th {
        padding: 12px 16px;
        font-size: 12px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
        white-space: nowrap;
    }

    .orders-table tbody tr.parent-row {
        transition: background 0.1s;
        cursor: pointer;
    }

    .orders-table tbody tr.parent-row:hover {
        background: #f8fafc;
    }

    .orders-table tbody tr.parent-row td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 14px;
        color: var(--c-text);
        vertical-align: middle;
    }

    .orders-table tbody tr.parent-row:last-child td {
        border-bottom: 1px solid #f1f3f5;
    }

    /* Order ID */
    .order-id-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .order-id-short {
        font-weight: 600;
        font-family: 'SF Mono', 'Menlo', monospace;
        font-size: 13px;
        color: var(--c-text);
    }

    .order-id-full {
        font-size: 11px;
        color: var(--c-text-muted);
        font-family: 'SF Mono', 'Menlo', monospace;
    }

    /* Customer info */
    .customer-cell {
        display: flex;
        flex-direction: column;
    }

    .customer-name {
        font-weight: 600;
        font-size: 14px;
        color: var(--c-text);
    }

    .customer-email {
        font-size: 12px;
        color: var(--c-text-muted);
    }

    /* Tracking badge */
    .tracking-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #7c3aed;
        background: #f5f3ff;
        padding: 2px 7px;
        border-radius: 4px;
        margin-top: 2px;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Status Badges */
    .order-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .order-status-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .order-status-badge.pending { background: #fffbeb; color: #d97706; }
    .order-status-badge.pending .dot { background: #f59e0b; }
    .order-status-badge.processing { background: #eff6ff; color: #2563eb; }
    .order-status-badge.processing .dot { background: #3b82f6; }
    .order-status-badge.shipped { background: #f5f3ff; color: #7c3aed; }
    .order-status-badge.shipped .dot { background: #8b5cf6; }
    .order-status-badge.delivered { background: #f0fdf4; color: #16a34a; }
    .order-status-badge.delivered .dot { background: #22c55e; }
    .order-status-badge.cancelled { background: #fef2f2; color: #dc2626; }
    .order-status-badge.cancelled .dot { background: #ef4444; }

    /* Payment Badge */
    .payment-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .payment-badge .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .payment-badge.paid { background: #f0fdf4; color: #16a34a; }
    .payment-badge.paid .dot { background: #22c55e; }
    .payment-badge.unpaid { background: #fef2f2; color: #dc2626; }
    .payment-badge.unpaid .dot { background: #ef4444; }

    /* Payment method label */
    .payment-method {
        font-size: 11px;
        color: var(--c-text-muted);
        display: block;
        margin-top: 1px;
    }

    /* Items count badge */
    .items-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #f3f4f6;
        color: var(--c-text);
    }

    /* Price */
    .order-total {
        font-weight: 700;
        font-size: 15px;
        color: var(--c-text);
    }

    .order-shipping {
        font-size: 11px;
        color: var(--c-text-muted);
        display: block;
    }

    /* Date */
    .order-date {
        font-size: 13px;
        color: var(--c-text);
    }

    .order-date-relative {
        font-size: 11px;
        color: var(--c-text-muted);
        display: block;
    }

    /* Expand icon */
    .expand-icon {
        font-size: 14px;
        color: #9ca3af;
        transition: transform 0.2s;
        display: inline-block;
    }

    .parent-row.expanded .expand-icon {
        transform: rotate(90deg);
    }

    /* Detail row */
    .order-detail-row {
        display: none;
    }

    .order-detail-row.visible {
        display: table-row;
    }

    .order-detail-row td {
        padding: 0 !important;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .order-detail-inner {
        padding: 20px 24px 20px 48px;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr 1fr;
        gap: 24px;
    }

    .order-detail-section h4 {
        font-size: 12px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .order-detail-section h4 i {
        font-size: 14px;
    }

    .order-items-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .order-items-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f1f3f5;
        font-size: 13px;
    }

    .order-items-list li:last-child {
        border-bottom: none;
    }

    .order-items-list .item-name {
        color: var(--c-text);
        font-weight: 500;
    }

    .order-items-list .item-qty {
        color: var(--c-text-muted);
        font-size: 12px;
        margin: 0 8px;
    }

    .order-items-list .item-price {
        font-weight: 600;
        color: var(--c-text);
        white-space: nowrap;
    }

    .order-detail-address {
        font-size: 13px;
        line-height: 1.6;
        color: var(--c-text);
    }

    .order-detail-address .label {
        color: var(--c-text-muted);
        font-size: 11px;
        font-weight: 600;
        display: block;
        margin-top: 6px;
    }

    .order-detail-address .label:first-child {
        margin-top: 0;
    }

    /* Timeline */
    .order-timeline {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .order-timeline li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding-bottom: 14px;
        position: relative;
    }

    .order-timeline li:last-child {
        padding-bottom: 0;
    }

    .order-timeline .tl-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-top: 4px;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .order-timeline .tl-dot.completed { background: #22c55e; }
    .order-timeline .tl-dot.current { background: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
    .order-timeline .tl-dot.pending { background: #d1d5db; }
    .order-timeline .tl-dot.cancelled { background: #ef4444; }

    .order-timeline li:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 3.5px;
        top: 14px;
        bottom: -2px;
        width: 1px;
        background: #e5e7eb;
    }

    .order-timeline .tl-text {
        font-size: 13px;
        color: var(--c-text);
    }

    .order-timeline .tl-text small {
        color: var(--c-text-muted);
        font-size: 11px;
        display: block;
    }

    .order-timeline .tl-text.completed { color: var(--c-text); }
    .order-timeline .tl-text.current { color: #2563eb; font-weight: 600; }
    .order-timeline .tl-text.pending { color: #9ca3af; }
    .order-timeline .tl-text.cancelled { color: #dc2626; }

    /* Empty State */
    .order-empty-state {
        text-align: center;
        padding: 64px 24px;
    }

    .order-empty-state .empty-icon {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
        display: block;
    }

    .order-empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 8px;
    }

    .order-empty-state p {
        font-size: 14px;
        color: var(--c-text-muted);
        margin-bottom: 24px;
        line-height: 1.5;
    }

    /* Pagination */
    .orders-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-top: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .orders-pagination .pagination-info {
        font-size: 13px;
        color: var(--c-text-muted);
    }

    .orders-pagination .pagination-links {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .orders-pagination .pagination-links a,
    .orders-pagination .pagination-links span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        color: var(--c-text);
        transition: background 0.1s;
    }

    .orders-pagination .pagination-links a:hover {
        background: #f3f4f6;
    }

    .orders-pagination .pagination-links span.active {
        background: var(--c-text);
        color: #fff;
    }

    .orders-pagination .pagination-links span.disabled {
        color: #d1d5db;
    }

    /* hidden rows for filtering */
    .orders-table tbody tr.parent-row.hidden-row {
        display: none;
    }
</style>

<!-- Stats Bar -->
<div class="order-stats-bar">
    <div class="order-stat-card">
        <div class="stat-icon total"><i class="ph ph-shopping-cart"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $globalTotal }}</div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    <div class="order-stat-card">
        <div class="stat-icon pending"><i class="ph ph-clock"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $globalPending }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="order-stat-card">
        <div class="stat-icon processing"><i class="ph ph-gear"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $globalProcessing }}</div>
            <div class="stat-label">Processing</div>
        </div>
    </div>
    <div class="order-stat-card">
        <div class="stat-icon completed"><i class="ph ph-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $globalCompleted }}</div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    <div class="order-stat-card">
        <div class="stat-icon revenue"><i class="ph ph-currency-circle-dollar"></i></div>
        <div class="stat-info">
            <div class="stat-number">&#8369;{{ number_format($globalRevenue, 0) }}</div>
            <div class="stat-label">Revenue</div>
        </div>
    </div>
</div>

<section class="card" style="padding: 0; overflow: hidden;">
    <!-- Toolbar -->
    <div style="padding: 16px 20px 0 20px;">
        <div class="orders-toolbar">
            <div class="orders-search-wrapper">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="orderSearchInput" placeholder="Search by order ID or customer..." autocomplete="off">
            </div>
            <div class="orders-filter-wrapper">
                <select class="orders-filter-select" id="orderStatusFilter">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="orders-filter-wrapper">
                <select class="orders-filter-select" id="paymentFilter">
                    <option value="all">All payments</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table class="orders-table">
            <thead>
                <tr>
                    <th style="width: 30px;"></th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $address = $order->shipping_address ?? [];
                        $customerName = ($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? '');
                        $customerEmail = $address['phone'] ?? $order->user_id ?? '';
                        $items = $order->items ?? collect([]);
                        $itemCount = $items->sum('quantity');
                        $shortId = substr($order->id, 0, 8);
                        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                        $currentIdx = array_search($order->status, $statuses) ?: 0;
                        $timelineStatus = $order->status === 'cancelled' ? 'cancelled' : 'active';
                    @endphp
                    <tr class="parent-row" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}" data-payment="{{ $order->payment_status }}" onclick="toggleOrderDetail('{{ $order->id }}')">
                        <td>
                            <span class="expand-icon"><i class="ph ph-caret-right"></i></span>
                        </td>
                        <td>
                            <div class="order-id-cell">
                                <div>
                                    <div class="order-id-short">#{{ $shortId }}</div>
                                    <div class="order-id-full">{{ $order->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="customer-cell">
                                <span class="customer-name">{{ $customerName ?: 'Guest' }}</span>
                                @if($customerEmail)
                                    <span class="customer-email">{{ $customerEmail }}</span>
                                @endif
                                @if($order->tracking_number)
                                    <span class="tracking-badge"><i class="ph ph-package"></i> {{ $order->tracking_number }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="order-status-badge {{ $order->status }}">
                                <span class="dot"></span>
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="payment-badge {{ $order->payment_status }}">
                                <span class="dot"></span>
                                {{ ucfirst($order->payment_status) }}
                            </span>
                            <span class="payment-method">{{ ucfirst($order->payment_method) }}</span>
                        </td>
                        <td>
                            <span class="items-badge">{{ $itemCount }}</span>
                        </td>
                        <td>
                            <span class="order-total">&#8369;{{ number_format((float) $order->total, 2) }}</span>
                            @if((float) $order->shipping_fee > 0)
                                <span class="order-shipping">+ &#8369;{{ number_format((float) $order->shipping_fee, 2) }} shipping</span>
                            @endif
                        </td>
                        <td>
                            <span class="order-date">{{ $order->created_at->format('M d, Y') }}</span>
                            <span class="order-date-relative">{{ $order->created_at->diffForHumans() }}</span>
                        </td>
                    </tr>
                    <tr class="order-detail-row" id="detail-{{ $order->id }}">
                        <td colspan="8">
                            <div class="order-detail-inner">
                                <!-- Left: Order Items -->
                                <div class="order-detail-section">
                                    <h4><i class="ph ph-box"></i> Items ({{ $itemCount }})</h4>
                                    <ul class="order-items-list">
                                        @forelse ($items as $item)
                                            <li>
                                                <span class="item-name">{{ $item->name }}</span>
                                                <span>
                                                    <span class="item-qty">x{{ $item->quantity }}</span>
                                                    <span class="item-price">&#8369;{{ number_format((float) $item->price, 2) }}</span>
                                                </span>
                                            </li>
                                        @empty
                                            <li style="color: #9ca3af; font-size: 13px; padding: 8px 0;">No items recorded.</li>
                                        @endforelse
                                    </ul>
                                </div>

                                <!-- Middle: Shipping Address -->
                                <div class="order-detail-section">
                                    <h4><i class="ph ph-map-pin"></i> Shipping Address</h4>
                                    <div class="order-detail-address">
                                        <span class="label">Name</span>
                                        {{ $customerName ?: 'N/A' }}

                                        <span class="label">Phone</span>
                                        {{ $address['phone'] ?? 'N/A' }}

                                        <span class="label">Address</span>
                                        {{ $address['address'] ?? 'N/A' }}
                                        @if(!empty($address['city'])), {{ $address['city'] }}@endif
                                        @if(!empty($address['province'])), {{ $address['province'] }}@endif
                                        @if(!empty($address['zip'])), {{ $address['zip'] }}@endif
                                    </div>
                                </div>

                                <!-- Right: Timeline -->
                                <div class="order-detail-section">
                                    <h4><i class="ph ph-clock"></i> Timeline</h4>
                                    <ul class="order-timeline">
                                        @foreach ($statuses as $idx => $s)
                                            @php
                                                if ($order->status === 'cancelled') {
                                                    // For cancelled orders: show all preceding as pending, only cancelled as cancelled
                                                    $tlClass = $s === 'cancelled' ? 'cancelled' : 'pending';
                                                } else {
                                                    $tlClass = $idx < $currentIdx ? 'completed' : ($idx === $currentIdx ? 'current' : 'pending');
                                                }
                                                $tlLabel = ucfirst($s);
                                            @endphp
                                            <li>
                                                <span class="tl-dot {{ $tlClass }}"></span>
                                                <span class="tl-text {{ $tlClass }}">
                                                    {{ $tlLabel }}
                                                    @if($idx === $currentIdx && $order->status !== 'cancelled')
                                                        <small>Current</small>
                                                    @elseif($s === 'cancelled' && $order->status === 'cancelled')
                                                        <small>Cancelled</small>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="order-empty-state">
                                <i class="ph ph-shopping-cart empty-icon"></i>
                                <h3>No orders yet</h3>
                                <p>When customers place orders through your storefront,<br>they will appear here for processing.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
        <div class="orders-pagination">
            <div class="pagination-info">
                Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders
            </div>
            <div class="pagination-links">
                @if ($orders->onFirstPage())
                    <span class="disabled"><i class="ph ph-caret-left"></i></span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}"><i class="ph ph-caret-left"></i></a>
                @endif

                @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                    @if ($page == $orders->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}"><i class="ph ph-caret-right"></i></a>
                @else
                    <span class="disabled"><i class="ph ph-caret-right"></i></span>
                @endif
            </div>
        </div>
    @endif
</section>

<script>
    function toggleOrderDetail(orderId) {
        var detailRow = document.getElementById('detail-' + orderId);
        if (!detailRow) return;

        var parentRow = detailRow.previousElementSibling;
        if (!parentRow) return;

        var isVisible = detailRow.classList.contains('visible');

        // Close all other open details
        var allVisible = document.querySelectorAll('.order-detail-row.visible');
        for (var i = 0; i < allVisible.length; i++) {
            if (allVisible[i] !== detailRow) {
                allVisible[i].classList.remove('visible');
                var prevParent = allVisible[i].previousElementSibling;
                if (prevParent) prevParent.classList.remove('expanded');
            }
        }

        if (isVisible) {
            detailRow.classList.remove('visible');
            parentRow.classList.remove('expanded');
        } else {
            detailRow.classList.add('visible');
            parentRow.classList.add('expanded');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Search & Filter
        var searchInput = document.getElementById('orderSearchInput');
        var statusFilter = document.getElementById('orderStatusFilter');
        var paymentFilter = document.getElementById('paymentFilter');
        var rows = document.querySelectorAll('.orders-table tbody tr.parent-row');

        function filterOrders() {
            var query = (searchInput ? searchInput.value.toLowerCase().trim() : '');
            var status = statusFilter ? statusFilter.value : 'all';
            var payment = paymentFilter ? paymentFilter.value : 'all';

            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var rowStatus = row.getAttribute('data-status');
                var rowPayment = row.getAttribute('data-payment');
                var text = (row.textContent || '').toLowerCase();

                var matchesSearch = !query || text.indexOf(query) !== -1;
                var matchesStatus = status === 'all' || rowStatus === status;
                var matchesPayment = payment === 'all' || rowPayment === payment;

                if (matchesSearch && matchesStatus && matchesPayment) {
                    row.classList.remove('hidden-row');
                } else {
                    row.classList.add('hidden-row');
                    // Also hide the detail row if the parent is hidden
                    var detail = document.getElementById('detail-' + row.getAttribute('data-order-id'));
                    if (detail && detail.classList.contains('visible')) {
                        detail.classList.remove('visible');
                        row.classList.remove('expanded');
                    }
                }
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterOrders);
        if (statusFilter) statusFilter.addEventListener('change', filterOrders);
        if (paymentFilter) paymentFilter.addEventListener('change', filterOrders);
    });
</script>
@endsection
