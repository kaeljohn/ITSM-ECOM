@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';

    $totalCount = $listings->count();
    $activeCount = $listings->where('status', 'active')->count();
    $draftCount = $listings->where('status', 'draft')->count();
    $archivedCount = $listings->where('status', 'archived')->count();
@endphp
@extends('ecommerce::admin.layout', ['title' => 'Storefront Listings', 'heading' => 'Storefront Listings'])

@section('content')
<style>
    /* Stats Bar */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .stat-card .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-card .stat-icon.total { background: #eff6ff; color: #2563eb; }
    .stat-card .stat-icon.active { background: #f0fdf4; color: #16a34a; }
    .stat-card .stat-icon.draft { background: #f9fafb; color: #6b7280; }
    .stat-card .stat-icon.archived { background: #fef2f2; color: #dc2626; }

    .stat-card .stat-info .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }

    .stat-card .stat-info .stat-label {
        font-size: 13px;
        color: var(--c-text-muted);
        font-weight: 500;
    }

    /* Toolbar */
    .listings-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: nowrap;
    }

    .search-wrapper {
        position: relative;
        flex: 1;
        min-width: 0;
    }

    .search-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
        pointer-events: none;
    }

    .search-wrapper input {
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

    .search-wrapper input:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .search-wrapper input::placeholder {
        color: #9ca3af;
        opacity: 1;
    }

    .filter-select-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .filter-select-wrapper::after {
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

    .filter-select {
        padding: 8px 30px 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 13px;
        color: var(--c-text);
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s;
        min-width: 130px;
        max-width: 160px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .filter-select:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: 0;
        border-radius: 8px;
        background: var(--c-text);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-add:hover {
        background: #1a2a47;
    }

    .btn-add i {
        font-size: 15px;
    }

    /* Table Enhancements */
    .listings-table {
        width: 100%;
        border-collapse: collapse;
    }

    .listings-table thead th {
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

    .listings-table thead th:first-child {
        border-radius: 0;
    }

    .listings-table tbody tr {
        transition: background 0.1s;
    }

    .listings-table tbody tr:hover {
        background: #f8fafc;
    }

    .listings-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 14px;
        color: var(--c-text);
        vertical-align: middle;
    }

    .listings-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Product cell with thumbnail */
    .product-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--c-border);
        flex-shrink: 0;
        background: #f9fafb;
    }

    .product-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 16px;
        flex-shrink: 0;
        border: 1px solid var(--c-border);
    }

    .product-name {
        font-weight: 600;
        color: var(--c-text);
    }

    .product-sku {
        font-size: 12px;
        color: var(--c-text-muted);
        display: block;
        margin-top: 1px;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .status-badge.active {
        background: #f0fdf4;
        color: #16a34a;
    }

    .status-badge.active .dot { background: #16a34a; }

    .status-badge.draft {
        background: #f9fafb;
        color: #6b7280;
    }

    .status-badge.draft .dot { background: #9ca3af; }

    .status-badge.archived {
        background: #fef2f2;
        color: #dc2626;
    }

    .status-badge.archived .dot { background: #dc2626; }

    /* Quantity Badge */
    .qty-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        padding: 2px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        background: #f3f4f6;
        color: var(--c-text);
    }

    .qty-badge.low {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Price */
    .price-value {
        font-weight: 700;
        font-size: 15px;
        color: var(--c-text);
    }

    /* Action Dropdown */
    .action-cell {
        text-align: right;
        position: relative;
    }

    .action-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.15s;
    }

    .action-trigger:hover {
        background: #f3f4f6;
        border-color: var(--c-border);
        color: var(--c-text);
    }

    .action-dropdown {
        position: absolute;
        right: 0;
        top: calc(100% + 4px);
        min-width: 160px;
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        z-index: 30;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-4px);
        transition: all 0.12s;
        overflow: hidden;
    }

    .action-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .action-dropdown a,
    .action-dropdown button {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 14px;
        border: 0;
        background: transparent;
        font-size: 13px;
        font-weight: 500;
        color: var(--c-text);
        text-decoration: none;
        text-align: left;
        cursor: pointer;
        transition: background 0.1s;
    }

    .action-dropdown a:hover,
    .action-dropdown button:hover {
        background: #f8fafc;
    }

    .action-dropdown a i,
    .action-dropdown button i {
        font-size: 16px;
        color: var(--c-text-muted);
    }

    .action-dropdown .delete-action {
        border-top: 1px solid var(--c-border);
        color: #dc2626;
    }

    .action-dropdown .delete-action i {
        color: #dc2626;
    }

    .action-dropdown .delete-action:hover {
        background: #fef2f2;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 64px 24px;
    }

    .empty-state .empty-icon {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
        display: block;
    }

    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: var(--c-text-muted);
        margin-bottom: 24px;
        line-height: 1.5;
    }

    /* hidden rows for filtering */
    .listings-table tbody tr.hidden-row {
        display: none;
    }
</style>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-icon total"><i class="ph ph-boxes"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $totalCount }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon active"><i class="ph ph-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $activeCount }}</div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon draft"><i class="ph ph-pencil"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $draftCount }}</div>
            <div class="stat-label">Draft</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon archived"><i class="ph ph-archive"></i></div>
        <div class="stat-info">
            <div class="stat-number">{{ $archivedCount }}</div>
            <div class="stat-label">Archived</div>
        </div>
    </div>
</div>

<section class="card" style="padding: 0; overflow: hidden;">
    <!-- Toolbar -->
    <div style="padding: 16px 20px 0 20px;">
        <div class="listings-toolbar">
            <div class="search-wrapper">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by name or SKU..." autocomplete="off">
            </div>
            <div class="filter-select-wrapper">
                <select class="filter-select" id="statusFilter">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <a class="btn-add" href="{{ route('ecommerce.admin.listings.create') }}">
                <i class="ph ph-plus"></i> Add listing
            </a>
        </div>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table class="listings-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Available</th>
                    <th>Price</th>
                    <th style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listings as $listing)
                    <tr data-status="{{ $listing->status }}">
                        <td>
                            <div class="product-cell">
                                @if($listing->image_url)
                                    <img class="product-thumb" src="{{ asset('storage/' . $listing->image_url) }}" alt="{{ $listing->name }}">
                                @else
                                    <div class="product-thumb-placeholder">
                                        <i class="ph ph-box"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="product-name">{{ $listing->name }}</div>
                                    <span class="product-sku">{{ $listing->sku }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $listing->status }}">
                                <span class="dot"></span>
                                {{ ucfirst($listing->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="qty-badge {{ $listing->available_quantity < 5 ? 'low' : '' }}">
                                {{ $listing->available_quantity }}
                            </span>
                        </td>
                        <td>
                            <span class="price-value">&#8369;{{ number_format((float) $listing->price, 2) }}</span>
                        </td>
                        <td class="action-cell">
                            <button type="button" class="action-trigger" onclick="toggleActionDropdown(this)" data-dropdown-id="dropdown-{{ $listing->id }}">
                                <i class="ph ph-dots-three-vertical"></i>
                            </button>
                            <div class="action-dropdown" id="dropdown-{{ $listing->id }}">
                                <a href="{{ route('ecommerce.admin.listings.edit', $listing) }}">
                                    <i class="ph ph-pencil"></i> Edit
                                </a>
                                <form method="post" action="{{ route('ecommerce.admin.listings.destroy', $listing) }}" data-delete-form data-name="{{ $listing->name }}" style="margin: 0;">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="delete-action">
                                        <i class="ph ph-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="ph ph-package empty-icon"></i>
                                <h3>No listings yet</h3>
                                <p>Get started by adding your first storefront listing.<br>Each listing attaches an active, Manufacturing-managed BOM.</p>
                                <a class="btn-add" href="{{ route('ecommerce.admin.listings.create') }}" style="margin: 0 auto;">
                                    <i class="ph ph-plus"></i> Add your first listing
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
    // Inline dropdown toggle — uses data-dropdown-id + getElementById
    function toggleActionDropdown(button) {
        var targetId = button.getAttribute('data-dropdown-id');
        if (!targetId) return;
        var dropdown = document.getElementById(targetId);
        if (!dropdown) return;
        
        // Close all other open dropdowns
        var allOpen = document.querySelectorAll('.action-dropdown.open');
        for (var i = 0; i < allOpen.length; i++) {
            if (allOpen[i] !== dropdown) {
                allOpen[i].classList.remove('open');
            }
        }
        
        // Toggle current
        dropdown.classList.toggle('open');
    }

    // Close dropdowns when clicking outside (only if NOT clicking a trigger or inside a dropdown)
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest ? e.target.closest('[data-dropdown-id]') : null;
        var insideDropdown = e.target.closest ? e.target.closest('.action-dropdown') : null;
        if (!trigger && !insideDropdown) {
            var allOpen = document.querySelectorAll('.action-dropdown.open');
            for (var i = 0; i < allOpen.length; i++) {
                allOpen[i].classList.remove('open');
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirm (safe — reads from data-name, not inline JS)
        var deleteForms = document.querySelectorAll('[data-delete-form]');
        for (var i = 0; i < deleteForms.length; i++) {
            (function(form) {
                var name = form.getAttribute('data-name') || 'this listing';
                form.addEventListener('submit', function(e) {
                    if (!confirm('Delete "' + name + '"? This cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            })(deleteForms[i]);
        }

        // Search
        var searchInput = document.getElementById('searchInput');
        var statusFilter = document.getElementById('statusFilter');
        var rows = document.querySelectorAll('.listings-table tbody tr[data-status]');

        if (searchInput && statusFilter) {
            function filterRows() {
                var query = searchInput.value.toLowerCase().trim();
                var status = statusFilter.value;

                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var rowStatus = row.getAttribute('data-status');
                    var nameEl = row.querySelector('.product-name');
                    var skuEl = row.querySelector('.product-sku');
                    var text = ((nameEl ? nameEl.textContent : '') + ' ' + (skuEl ? skuEl.textContent : '')).toLowerCase();

                    var matchesSearch = !query || text.indexOf(query) !== -1;
                    var matchesStatus = status === 'all' || rowStatus === status;

                    if (matchesSearch && matchesStatus) {
                        row.classList.remove('hidden-row');
                    } else {
                        row.classList.add('hidden-row');
                    }
                }
            }

            searchInput.addEventListener('input', filterRows);
            statusFilter.addEventListener('change', filterRows);
        }
    });
</script>
@endsection
