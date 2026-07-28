@extends('ecommerce::admin.layout', ['title' => 'Edit Storefront', 'heading' => 'Edit Storefront', 'hideLayout' => true])

@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';

    $sections = collect($layout['sections'] ?? [])->keyBy('id');
    $hero = $sections->get('hero', []);
    $listings = $sections->get('featured_listings', []);
    $promo = $sections->get('promo', []);
    $benefits = $sections->get('benefits', []);
    $tiers = $sections->get('tiers', []);
    $tiersBlocks = $tiers['blocks'] ?? [];
    $prebuilts = $sections->get('prebuilts', []);
    $prebuiltsBlocks = $prebuilts['blocks'] ?? [];
    $categories = $sections->get('categories', []);
    $cta = $sections->get('cta', []);
    $order = implode(',', array_column($layout['sections'] ?? [], 'id'));

    $navbar = $layout['navbar'] ?? [];
    $footer = $layout['footer'] ?? [];
    $links = $navbar['links'] ?? [];
    $customPages = $layout['custom_pages'] ?? [];
    $supportPagesData = $layout['support_pages'] ?? [];
    $companyPagesData = $layout['company_pages'] ?? [];

    // Merge with defaults so any new keys (e.g. faq_items for contact) are present
    // even if the saved layout still has the old structure.
    $defaultLayout = \Modules\Ecommerce\Models\StorefrontLayout::defaultFor(new \App\Models\Company());
    $defaultSupportPages = $defaultLayout['support_pages'] ?? [];
    $defaultCompanyPages = $defaultLayout['company_pages'] ?? [];

    foreach ($defaultSupportPages as $pageKey => $defaultData) {
        if (isset($supportPagesData[$pageKey])) {
            $supportPagesData[$pageKey] = array_merge($defaultData, $supportPagesData[$pageKey]);
        } else {
            $supportPagesData[$pageKey] = $defaultData;
        }
    }
    foreach ($defaultCompanyPages as $pageKey => $defaultData) {
        if (isset($companyPagesData[$pageKey])) {
            $companyPagesData[$pageKey] = array_merge($defaultData, $companyPagesData[$pageKey]);
        } else {
            $companyPagesData[$pageKey] = $defaultData;
        }
    }
    $allowedSupportPages = ['contact', 'shipping', 'returns'];
    $allowedCompanyPages = ['about', 'careers', 'affiliates'];
    $isSupportPage = in_array($context, $allowedSupportPages);
    $isCompanyPage = in_array($context, $allowedCompanyPages);

    $editorAdmin = auth('ecommerce_admin')->user();
    $editorCompany = $editorAdmin?->getCompany();
    $editorCompanyName = $editorCompany?->company_name ?? 'Store';
    $editorCompanyLogo = $editorCompany?->logoUrl();
    $editorHasPublished = ($clientId = $editorCompany?->id) ? \Modules\Ecommerce\Models\StorefrontLayout::where('client_id', $clientId)->whereNotNull('published_layout')->exists() : false;
    $editorAdminName = trim(($editorAdmin?->first_name ?? '') . ' ' . ($editorAdmin?->last_name ?? '')) ?: $editorCompanyName;
    $editorAdminEmail = $editorAdmin?->email ?? $editorAdmin?->company_email ?? '';
    $editorInitials = strtoupper(substr($editorAdminName, 0, 2));

    $context = $context ?? 'home';
    $isHome = $context === 'home';
    $currentPage = collect($customPages)->firstWhere('slug', $context);

    $previewUrl = route('ecommerce.admin.layout.preview', ['context' => $context, 'preview' => 1, 't' => time()]);
@endphp

@section('content')
<style>
    body { overflow: hidden; margin: 0; background: #f4f6f8; font-family: Inter, Arial, sans-serif; }
    .page { width: 100% !important; max-width: 100% !important; padding: 0 !important; display: flex; flex-direction: column; height: 100vh; }
    .page-heading { display: none; }
    .success { display: none; }

    /* Top Bar */
    .editor-topbar { background: var(--c-header-bg, #132B52); height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; border-bottom: 1px solid rgba(255,255,255,0.08); z-index: 50; }
    .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .topbar-left a, .topbar-back-btn { color: rgba(255,255,255,0.6); display: flex; align-items: center; text-decoration: none; padding: 6px; border-radius: 6px; transition: all 0.15s; }
    .topbar-left a:hover, .topbar-back-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

    /* Company logo in editor topbar */
    .editor-company-logo { display: flex; align-items: center; flex-shrink: 0; }
    .editor-company-logo img { height: 24px; max-width: 80px; object-fit: contain; }
    .editor-company-logo .no-logo { font-size: 13px; font-weight: 700; color: #fff; white-space: nowrap; }



    .editor-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.12); flex-shrink: 0; }

    /* Store status pill in dark theme */
    .editor-store-status { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; flex-shrink: 0; }
    .editor-store-status .dot { width: 5px; height: 5px; border-radius: 50%; }
    .editor-store-status.live { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
    .editor-store-status.live .dot { background: #22c55e; }
    .editor-store-status.draft { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .editor-store-status.draft .dot { background: #f59e0b; }

    .topbar-center { position: relative; }
    .custom-page-dropdown { position: relative; }
    .page-dropdown-trigger { display: flex; align-items: center; background: #ffffff; border: 1px solid #e1e3e5; border-radius: 8px; padding: 4px 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); cursor: pointer; transition: all 0.2s ease; outline: none; }
    .page-dropdown-trigger:hover { border-color: #c9cccf; background: #fafbfb; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
    .page-dropdown-trigger:focus, .custom-page-dropdown.open .page-dropdown-trigger { border-color: #008060; box-shadow: 0 0 0 2px rgba(0, 128, 96, 0.15); background: #fff; }
    .page-select-icon { color: #5c5f62; display: flex; align-items: center; margin-right: 8px; }
    .selected-page-title { font-weight: 600; font-size: 13px; color: #202223; margin-right: 4px; }
    .context-badge { background: #e6f4ea; color: #008060; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; margin-left: 6px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #b7e1cd; }
    .context-badge::before { content: ''; display: inline-block; width: 6px; height: 6px; background: #008060; border-radius: 50%; }
    .dropdown-chevron { color: #5c5f62; margin-left: 8px; transition: transform 0.2s ease; }
    .custom-page-dropdown.open .dropdown-chevron { transform: rotate(180deg); }

    /* Custom Dropdown Popover Menu */
    .page-dropdown-menu { display: none; position: absolute; top: calc(100% + 6px); left: 50%; transform: translateX(-50%); width: 280px; background: #ffffff; border: 1px solid #e1e3e5; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 4px 6px -2px rgba(0,0,0,0.05); z-index: 1000; overflow: hidden; animation: dropdownFadeIn 0.15s ease-out; }
    @keyframes dropdownFadeIn { from { opacity: 0; transform: translate(-50%, -6px); } to { opacity: 1; transform: translate(-50%, 0); } }
    .custom-page-dropdown.open .page-dropdown-menu { display: block; }
    .dropdown-menu-header { padding: 10px 14px 6px; font-size: 11px; font-weight: 700; color: #6d7175; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f2f3; background: #fafbfb; }
    .dropdown-section-label { padding: 10px 10px 4px; font-size: 10px; font-weight: 700; color: #8c9196; text-transform: uppercase; letter-spacing: 0.6px; display: flex; align-items: center; gap: 6px; }
    .dropdown-section-divider { height: 1px; background: #e1e3e5; margin: 6px 0; }
    .dropdown-menu-list { padding: 6px; max-height: 380px; overflow-y: auto; }
    .dropdown-menu-list::-webkit-scrollbar { width: 4px; }
    .dropdown-menu-list::-webkit-scrollbar-thumb { background: #e1e3e5; border-radius: 4px; }
    .page-dropdown-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; text-decoration: none; border-radius: 8px; transition: all 0.15s ease; color: #202223; margin-bottom: 2px; }
    .page-dropdown-item:last-child { margin-bottom: 0; }
    .page-dropdown-item:hover { background: #f4f6f8; text-decoration: none; color: #202223; }
    .page-dropdown-item.active { background: #f0f7f4; color: #008060; }
    .page-dropdown-item .item-icon { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: #f4f6f8; color: #5c5f62; flex-shrink: 0; transition: all 0.15s ease; }
    .page-dropdown-item:hover .item-icon { background: #e1e3e5; color: #202223; }
    .page-dropdown-item.active .item-icon { background: #008060; color: #ffffff; }
    .page-dropdown-item .item-details { display: flex; flex-direction: column; flex-grow: 1; overflow: hidden; }
    .page-dropdown-item .item-title { font-size: 13px; font-weight: 600; line-height: 1.2; }
    .page-dropdown-item .item-desc { font-size: 11px; color: #6d7175; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .page-dropdown-item.active .item-desc { color: #008060; opacity: 0.85; }
    .page-dropdown-item .active-check { color: #008060; display: flex; align-items: center; }

    .topbar-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; color: rgba(255,255,255,0.6); border: none; background: transparent; cursor: pointer; transition: all 0.15s; }
    .icon-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

    .topbar-sep { width: 1px; height: 24px; background: rgba(255,255,255,0.12); margin: 0 4px; flex-shrink: 0; }

    .save-btn-top { background: #1B6FC8; color: #fff; padding: 6px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease; }
    .save-btn-top:hover { background: #1558a3; }
    .save-btn-top:active { transform: scale(0.95); }
    .save-btn-top.outline { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.8); }
    .save-btn-top.outline:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.3); color: #fff; }

    .builder-container { display: flex; height: calc(100vh - 56px); overflow: hidden; background: #f4f6f8; width: 100%; }

    /* Rich Text Editor (Used by component) */
    .rt-container { border: 1px solid #c9cccf; border-radius: 4px; background: #fff; overflow: hidden; margin-top: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    .rt-container:focus-within { border-color: #008060; box-shadow: 0 0 0 1px #008060; }
    .rt-toolbar { display: flex; align-items: center; gap: 4px; padding: 6px; border-bottom: 1px solid #e1e3e5; background: #f9fafb; flex-wrap: wrap; }
    .rt-btn { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; border-radius: 4px; cursor: pointer; color: #5c5f62; font-family: serif; font-size: 14px; }
    .rt-btn:hover { background: #e1e3e5; color: #202223; }
    .rt-btn.bold { font-weight: bold; font-family: sans-serif; }
    .rt-btn.italic { font-style: italic; }
    .rt-editor { padding: 12px; min-height: 80px; max-height: 200px; overflow-y: auto; font-size: 14px; color: #202223; line-height: 1.5; outline: none; background: #fff; }
    .rt-editor p { margin: 0 0 8px 0; }
    .rt-editor p:last-child { margin: 0; }
    .rt-editor h2 { font-size: 1.5em; font-weight: bold; margin: 0 0 8px 0; }
    .rt-editor ul { margin: 0 0 8px 0; padding-left: 20px; list-style-type: disc; }
    .rt-editor ol { margin: 0 0 8px 0; padding-left: 20px; list-style-type: decimal; }

    .label-header { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 500; color: #202223; margin-bottom: 4px; }

    /* Left Sidebar Styling */
    .builder-sidebar { width: 300px; min-width: 300px; background: #fff; color: #202223; overflow-y: auto; display: flex; flex-direction: column; z-index: 10; border-radius: 8px; margin: 8px 0 8px 8px; box-shadow: 0 0 0 1px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.05); }
    .builder-sidebar::-webkit-scrollbar { width: 4px; }
    .builder-sidebar::-webkit-scrollbar-thumb { background: #e1e3e5; border-radius: 4px; }

    .sidebar-header { padding: 16px; font-size: 14px; font-weight: 600; border-bottom: 1px solid #e1e3e5; display: flex; justify-content: space-between; align-items: center; background: #fff; }

    .sidebar-group-title { padding: 16px 16px 8px; font-size: 14px; font-weight: 600; color: #202223; }

    .nav-item-wrapper { display: flex; flex-direction: column; }
    .nav-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; cursor: pointer; font-size: 13px; color: #202223; border-left: 3px solid transparent; }
    .nav-item:hover { background: #f4f6f8; }

    .nav-item-left { display: flex; align-items: center; gap: 8px; flex: 1; }
    .chevron-toggle { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; color: #5c5f62; border-radius: 4px; transition: transform 0.2s; }
    .chevron-toggle:hover { background: #e1e3e5; }
    .nav-item-wrapper.expanded .chevron-toggle { transform: rotate(90deg); }

    .nav-item-left > svg { width: 16px; height: 16px; color: #5c5f62; }

    .sub-items { display: none; flex-direction: column; padding-bottom: 8px; }
    .nav-item-wrapper.expanded .sub-items { display: flex; }

    .sub-item { display: flex; align-items: center; gap: 12px; padding: 6px 16px 6px 48px; font-size: 13px; color: #202223; cursor: pointer; border-radius: 4px; margin: 2px 8px; }
    .sub-item:hover { background: #f4f6f8; }
    .sub-item.active { background: #0060df; color: #fff; font-weight: 500; }
    .sub-item.active svg { color: #fff; }
    .sub-item svg { width: 16px; height: 16px; color: #5c5f62; }

    .add-block-btn { display: flex; align-items: center; gap: 8px; color: #2c6ecb; font-size: 13px; padding: 6px 16px 6px 48px; background: transparent; border: none; cursor: pointer; width: 100%; text-align: left; }
    .add-block-btn:hover { text-decoration: underline; }
    .add-block-btn svg { color: #2c6ecb; width: 14px; height: 14px; }

    .add-section-btn { display: flex; align-items: center; gap: 8px; color: #2c6ecb; font-size: 13px; padding: 8px 16px; background: transparent; border: none; cursor: pointer; width: 100%; text-align: left; margin-left: 16px;}
    .add-section-btn:hover { text-decoration: underline; }
    .add-section-btn svg { color: #2c6ecb; width: 14px; height: 14px; }

    /* Right Sidebar (Properties Panel) */
    .builder-right-sidebar { width: 320px; min-width: 320px; background: #fff; display: flex; flex-direction: column; z-index: 10; transition: transform 0.3s ease; transform: translateX(100%); position: absolute; right: 0; top: 56px; height: calc(100vh - 56px); border-radius: 8px; border-top-right-radius: 0; border-bottom-right-radius: 0; box-shadow: none; }
    .builder-right-sidebar.open { transform: translateX(0); box-shadow: -4px 0 15px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.05); }

    .right-panel-content { display: none; flex: 1; flex-direction: column; overflow-y: auto; }
    .right-panel-content.active { display: flex; }

    .panel-header { padding: 16px; font-size: 14px; font-weight: 600; border-bottom: 1px solid #e1e3e5; display: flex; align-items: center; justify-content: space-between; }
    .panel-header button { background: transparent; border: none; cursor: pointer; color: #5c5f62; padding: 4px; border-radius: 4px; }
    .panel-header button:hover { background: #f4f6f8; color: #202223; }

    .section-content { padding: 16px; }
    .section-content label { display: block; font-size: 13px; color: #202223; margin-bottom: 4px; font-weight: 500; margin-top: 16px; }
    .section-content label:first-child { margin-top: 0; }
    .section-content input, .section-content textarea, .section-content select { width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #c9cccf; border-radius: 4px; box-sizing: border-box; }
    .section-content input:focus, .section-content textarea:focus, .section-content select:focus { border-color: #2c6ecb; outline: 1px solid #2c6ecb; }

    /* Iframe Styling */
    .builder-preview { flex-grow: 1; position: relative; display: flex; flex-direction: column; padding: 16px; overflow: hidden; transition: margin-right 0.3s ease; }
    .builder-preview.panel-open { margin-right: 320px; }

    .builder-preview-inner { flex-grow: 1; background: #fff; border-radius: 8px; box-shadow: 0 0 0 1px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; position: relative; transform-origin: top left; transition: transform 0.3s ease; }

    .preview-header { padding: 8px 16px; border-bottom: 1px solid #e1e3e5; display: flex; justify-content: flex-end; align-items: center; background: #fff; }
    .status { font-size: 12px; color: #5c5f62; display: flex; align-items: center; gap: 8px; }

    .live-indicator { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; color: #2c6ecb; }
    .live-indicator .dot { width: 8px; height: 8px; background: #2c6ecb; border-radius: 50%; animation: pulse 2s infinite; }

    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(44, 110, 203, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(44, 110, 203, 0); } 100% { box-shadow: 0 0 0 0 rgba(44, 110, 203, 0); } }

    .builder-preview iframe { width: 100%; flex-grow: 1; border: none; }
    .property-group-title { padding: 16px 16px 0; font-size: 13px; font-weight: 600; color: #202223; border-top: 1px solid #e1e3e5; margin-top: 8px; }

    /* Publish Modal Styles */
    .publish-modal-backdrop { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.2s ease-in-out; pointer-events: none; }
    .publish-modal-backdrop.open { opacity: 1; pointer-events: auto; }
    .publish-modal-card { background: #fff; border-radius: 12px; max-width: 420px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); transform: translateY(10px) scale(0.98); transition: transform 0.2s ease-in-out; text-align: center; }
    .publish-modal-backdrop.open .publish-modal-card { transform: translateY(0) scale(1); }
    .publish-modal-icon { width: 48px; height: 48px; background: #e6f4ea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .publish-modal-title { font-size: 18px; font-weight: 700; color: #202223; margin-bottom: 8px; }
    .publish-modal-desc { font-size: 14px; color: #6d7175; line-height: 1.5; margin-bottom: 24px; }
    .publish-modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
    .publish-btn-cancel { flex: 1; padding: 10px 16px; border: 1px solid #c9cccf; background: #fff; color: #202223; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
    .publish-btn-cancel:hover { background: #f6f6f7; }
    .publish-btn-confirm { flex: 1; padding: 10px 16px; border: none; background: #008060; color: #fff; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
    .publish-btn-confirm:hover { background: #006e52; }

    /* Save Draft Loading Overlay */
    .save-loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(11, 30, 61, 0.7);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 20px;
    }
    .save-loading-overlay .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(255,255,255,0.15);
        border-top: 3px solid #fff;
        border-radius: 50%;
        animation: saveSpin 0.7s linear infinite;
    }
    @keyframes saveSpin {
        to { transform: rotate(360deg); }
    }
    .save-loading-overlay .loading-text {
        color: #fff;
        font-size: 15px;
        font-weight: 600;
    }
    .save-loading-overlay .loading-sub {
        color: rgba(255,255,255,0.5);
        font-size: 13px;
    }

    /* Page Loading Overlay */
    .page-loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(11, 30, 61, 0.85);
        backdrop-filter: blur(6px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 24px;
    }
    .page-loading-overlay .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid rgba(255,255,255,0.12);
        border-top: 4px solid #fff;
        border-radius: 50%;
        animation: saveSpin 0.7s linear infinite;
    }
    .page-loading-overlay .loading-text {
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .page-loading-overlay .loading-sub {
        color: rgba(255,255,255,0.5);
        font-size: 13px;
        margin-top: -16px;
    }

    /* User dropdown open state (editor topbar) */
    [data-user-menu][data-open="true"] [data-user-menu-dropdown] {
        visibility: visible !important;
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    [data-user-menu] [data-user-menu-dropdown] a:hover,
    [data-user-menu] [data-user-menu-dropdown] button:hover {
        background: #f5f5f5;
    }
    [data-user-menu] [data-user-menu-dropdown] button:hover {
        background: #fef2f2;
    }
</style>

<div class="editor-topbar">
    <div class="topbar-left">
        <a class="topbar-back-btn" href="{{ route('ecommerce.admin.dashboard') }}" title="Exit">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        </a>

        <div class="editor-company-logo">
            @if($editorCompanyLogo)
                <img src="{{ $editorCompanyLogo }}" alt="{{ $editorCompanyName }}">
            @else
                <span class="no-logo">{{ $editorCompanyName }}</span>
            @endif
        </div>

        <span class="editor-divider"></span>

        <span class="editor-store-status {{ $editorHasPublished ? 'live' : 'draft' }}">
            <span class="dot"></span>
            {{ $editorHasPublished ? 'Published' : 'Draft' }}
        </span>
    </div>

    <div class="topbar-center">
        <div class="custom-page-dropdown" id="customPageDropdown">
            <button type="button" class="page-dropdown-trigger" onclick="togglePageDropdown(event)" aria-haspopup="true" aria-expanded="false">
                <span class="page-select-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </span>
                <span class="selected-page-title">
                    @switch($context)
                        @case('collections') Collections @break
                        @case('categories/category1')
                        @case('store/accessories') Category Showcase 1 @break
                        @case('categories/category2')
                        @case('store/monitors') Category Showcase 2 @break
                        @case('categories/category3')
                        @case('store/pc-parts') Category Showcase 3 @break
                        @case('cart') Cart @break
                        @case('checkout') Checkout @break
                        @case('checkout-success') Checkout Success @break
                        @case('account/profile') Profile Settings @break
                        @case('account/order-history') Order History @break
                        @case('account/purchases') My Purchases @break
                        @case('search') Search @break
                        @case('notifications') Notifications @break
                        @case('about') About Us @break
                        @case('contact') Contact & FAQ @break
                        @case('careers') Careers @break
                        @case('affiliates') Affiliates @break
                        @case('shipping') Shipping & Delivery @break
                        @case('returns') Returns & Exchanges @break
                        @default Home page
                    @endswitch
                </span>
                <span class="context-badge">Active</span>
                <svg class="dropdown-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="page-dropdown-menu" id="pageDropdownMenu">
                <div class="dropdown-menu-header">Select Storefront Page</div>
                <div class="dropdown-menu-list">
                    <!-- Section: Storefront Pages -->
                    <div class="dropdown-section-label">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                        Storefront Pages
                    </div>

                    <a href="?context=home" class="page-dropdown-item @if($isHome || $context === 'home' || $context === 'storefront') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></span>
                        <div class="item-details">
                            <span class="item-title">Home page</span>
                            <span class="item-desc">Main storefront landing page</span>
                        </div>
                        @if($isHome || $context === 'home' || $context === 'storefront')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=collections" class="page-dropdown-item @if($context === 'collections') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></span>
                        <div class="item-details">
                            <span class="item-title">Collections</span>
                            <span class="item-desc">Catalog listing & product grid</span>
                        </div>
                        @if($context === 'collections')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=categories/category1" class="page-dropdown-item @if($context === 'categories/category1' || $context === 'store/accessories') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></span>
                        <div class="item-details">
                            <span class="item-title">Category Showcase 1</span>
                            <span class="item-desc">Curated category product layout</span>
                        </div>
                        @if($context === 'categories/category1' || $context === 'store/accessories')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=categories/category2" class="page-dropdown-item @if($context === 'categories/category2' || $context === 'store/monitors') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg></span>
                        <div class="item-details">
                            <span class="item-title">Category Showcase 2</span>
                            <span class="item-desc">Featured category product showcase</span>
                        </div>
                        @if($context === 'categories/category2' || $context === 'store/monitors')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=categories/category3" class="page-dropdown-item @if($context === 'categories/category3' || $context === 'store/pc-parts') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></span>
                        <div class="item-details">
                            <span class="item-title">Category Showcase 3</span>
                            <span class="item-desc">Custom category showcase layout</span>
                        </div>
                        @if($context === 'categories/category3' || $context === 'store/pc-parts')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <div class="dropdown-section-divider"></div>

                    <!-- Section: Support Pages -->
                    <div class="dropdown-section-label">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 16 12 12 12 8"/><line x1="12" y1="4" x2="12.01" y2="4"/></svg>
                        Support Pages
                    </div>

                    <a href="?context=contact" class="page-dropdown-item @if($context === 'contact') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">Contact & FAQ</span>
                            <span class="item-desc">Contact info, FAQ accordion, and message form</span>
                        </div>
                        @if($context === 'contact')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=shipping" class="page-dropdown-item @if($context === 'shipping') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">Shipping & Delivery</span>
                            <span class="item-desc">Shipping rates, processing time, and tracking</span>
                        </div>
                        @if($context === 'shipping')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=returns" class="page-dropdown-item @if($context === 'returns') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">Returns & Exchanges</span>
                            <span class="item-desc">Warranty, return policy, and process steps</span>
                        </div>
                        @if($context === 'returns')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <div class="dropdown-section-divider"></div>

                    <!-- Section: Company Pages -->
                    <div class="dropdown-section-label">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Company Pages
                    </div>

                    <a href="?context=about" class="page-dropdown-item @if($context === 'about') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">About Us</span>
                            <span class="item-desc">Store story, values, and information</span>
                        </div>
                        @if($context === 'about')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=careers" class="page-dropdown-item @if($context === 'careers') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="20 8 22 10 24 6"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">Careers</span>
                            <span class="item-desc">Open positions and company culture</span>
                        </div>
                        @if($context === 'careers')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>

                    <a href="?context=affiliates" class="page-dropdown-item @if($context === 'affiliates') active @endif">
                        <span class="item-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        <div class="item-details">
                            <span class="item-title">Affiliates</span>
                            <span class="item-desc">Partner program and benefits</span>
                        </div>
                        @if($context === 'affiliates')
                        <span class="active-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="topbar-right">
        <button class="save-btn-top outline" onclick="openSaveDraftModal()">Save Draft</button>
        <button type="button" class="save-btn-top" onclick="openPublishModal()">Publish Live</button>

        <span class="topbar-sep"></span>

        <!-- User Menu -->
        <div class="user-menu-wrap" data-user-menu style="position:relative;margin-left:2px;">
            <button type="button" class="user-avatar" data-user-menu-button style="width:30px;height:30px;border-radius:50%;border:0;background:var(--c-primary,#1B6FC8);color:#fff;font-weight:600;font-size:11px;cursor:pointer;display:grid;place-items:center;" aria-label="Open user menu" aria-expanded="false">
                {{ $editorInitials }}
            </button>
            <div class="user-dropdown" data-user-menu-dropdown style="visibility:hidden;position:absolute;z-index:100;top:38px;right:0;width:230px;overflow:hidden;border-radius:10px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,0.12);opacity:0;transform:translateY(-6px);transition:all 0.16s ease;border:1px solid #e1e3e5;">
                <div style="padding:14px;border-bottom:1px solid #e1e3e5;background:#fafbfc;">
                    <div style="font-size:14px;font-weight:600;color:#202223;">{{ $editorAdminName }}</div>
                    @if($editorAdminEmail)
                        <div style="font-size:12px;color:#6d7175;margin-top:2px;">{{ $editorAdminEmail }}</div>
                    @endif
                    <div style="font-size:11px;color:#2c6ecb;font-weight:600;margin-top:4px;display:flex;align-items:center;gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg> {{ $editorCompanyName }}
                    </div>
                </div>
                <a href="{{ route('ecommerce.admin.dashboard') }}" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 14px;border:0;background:#fff;color:#202223;font:500 13px Inter,Arial,sans-serif;text-decoration:none;cursor:pointer;transition:background 0.1s;box-sizing:border-box;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg> Admin Dashboard
                </a>
                <a href="#" onclick="alert('Settings coming soon!'); return false;" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 14px;border:0;background:#fff;color:#202223;font:500 13px Inter,Arial,sans-serif;text-decoration:none;cursor:pointer;transition:background 0.1s;box-sizing:border-box;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg> Settings
                </a>
                <hr style="border:0;border-top:1px solid #e1e3e5;margin:0;">
                <form method="post" action="{{ route('ecommerce.admin.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 14px;border:0;background:#fff;color:#dc2626;font:500 13px Inter,Arial,sans-serif;cursor:pointer;transition:background 0.1s;box-sizing:border-box;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<form id="layout-form" class="builder-container" method="post" enctype="multipart/form-data" action="{{ route('ecommerce.admin.layout.save') }}?context={{ $context }}">
    @csrf @method('put')
    <input id="section-order" type="hidden" name="section_order" value="{{ old('section_order', $order) }}">

    <!-- Left Sidebar (Main Tree) -->
    <div class="builder-sidebar" id="panel-main">
        <div class="sidebar-header">
            {{ $isHome ? 'Home page' : ($currentPage['title'] ?? 'Custom Page') }}
        </div>

        <div class="sidebar-group-title">Site Settings</div>

        <!-- Site Settings Section -->
        <div class="nav-item-wrapper" id="wrapper-sitesettings">
            <div class="nav-item nav-trigger" data-target="panel-sitesettings-main" onclick="openRightPanel('wrapper-sitesettings', 'panel-sitesettings-main')">
                <div class="nav-item-left">
                    <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-sitesettings')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Site Settings
                </div>
            </div>
            <div class="sub-items">
                <div class="sub-item" onclick="openRightPanel('wrapper-sitesettings', 'panel-sitesettings-storename'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg> Tab Store Name
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-sitesettings', 'panel-sitesettings-tagline'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg> Tab Tagline
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-sitesettings', 'panel-sitesettings-colors'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg> Site Color Scheme
                </div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #e1e3e5; margin: 16px 0 0;">

        <div class="sidebar-group-title">Header</div>

        <!-- Header / Navbar Section -->
        <div class="nav-item-wrapper" id="wrapper-header">
            <div class="nav-item nav-trigger" data-target="panel-header-main" onclick="openRightPanel('wrapper-header', 'panel-header-main')">
                <div class="nav-item-left">
                    <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-header')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                    Navbar
                </div>
            </div>
            <div class="sub-items">
                <div class="sub-item" onclick="openRightPanel('wrapper-header', 'panel-header-logo'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Logo
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-header', 'panel-header-name'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg> Name
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-header', 'panel-header-announcement'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg> Announcement Bar
                </div>
            </div>
        </div>

        <div class="nav-item-wrapper" id="wrapper-header-nav-buttons">
            <div class="nav-item nav-trigger" data-target="panel-header-nav-buttons-main" onclick="openRightPanel('wrapper-header-nav-buttons', 'panel-header-nav-buttons-main')">
                <div class="nav-item-left">
                    <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-header-nav-buttons')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Secondary Navigation
                </div>
            </div>
            <div class="sub-items">
                <div class="sub-item" onclick="openRightPanel('wrapper-header-nav-buttons', 'panel-header-nav-btn-1'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Link 1
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-header-nav-buttons', 'panel-header-nav-btn-2'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Link 2
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-header-nav-buttons', 'panel-header-nav-btn-3'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Link 3
                </div>
            </div>
        </div>



        <hr style="border: 0; border-top: 1px solid #e1e3e5; margin: 16px 0 0;">

        <div class="sidebar-group-title">Template</div>
        @if($isHome)
        @php
            $orderedSectionIds = array_column($layout['sections'] ?? [], 'id');
            $allAllowed = ['hero', 'tiers', 'prebuilts', 'categories', 'cta'];
            $sectionIdsToRender = array_values(array_unique(array_filter([...$orderedSectionIds, ...$allAllowed])));
        @endphp
        <div id="sortable-sections">
        @foreach($sectionIdsToRender as $secId)
            @if($secId === 'hero')
            <!-- Hero Section -->
            <div class="nav-item-wrapper" id="wrapper-hero" draggable="true" data-section-id="hero">
                <div class="nav-item nav-trigger" data-target="panel-hero-main" onclick="openRightPanel('wrapper-hero', 'panel-hero-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-hero')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Hero
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-heading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Heading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-subheading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Subheading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-button'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Button
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-stats'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10M18 20V4M6 20v-4"/></svg> Stats Row
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-image'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Main Image
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-hero', 'panel-hero-marquee'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 5H19V11M19 5L5 19"/></svg> Features Marquee
                    </div>
                </div>
            </div>
            @elseif($secId === 'tiers')
            <!-- Tiers Section -->
            <div class="nav-item-wrapper" id="wrapper-tiers" draggable="true" data-section-id="tiers">
                <div class="nav-item nav-trigger" data-target="panel-tiers-main" onclick="openRightPanel('wrapper-tiers', 'panel-tiers-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-tiers')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        Tiers
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-tiers', 'panel-tiers-heading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Heading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-tiers', 'panel-tiers-subheading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Subheading
                    </div>

                    @php $tiers = $layout['sections'][array_search('tiers', array_column($layout['sections'], 'id'))] ?? []; @endphp
                    @php $tiersBlocks = $tiers['blocks'] ?? []; @endphp
                    <div id="tiers-blocks-container">
                        @foreach($tiersBlocks as $idx => $block)
                        <div class="sub-item tiers-block-nav" onclick="openRightPanel('wrapper-tiers', 'panel-tiers-block-{{ $idx }}'); highlightSub(this);" id="nav-tiers-block-{{ $idx }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg> <span>Item {{ $idx + 1 }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="sub-item add-section-btn" onclick="addBlock('tiers')" style="color:#2c6ecb; padding-left:32px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add item card
                    </div>
                </div>
            </div>
            @elseif($secId === 'prebuilts')
            <!-- Prebuilts Section -->
            <div class="nav-item-wrapper" id="wrapper-prebuilts" draggable="true" data-section-id="prebuilts">
                <div class="nav-item nav-trigger" data-target="panel-prebuilts-main" onclick="openRightPanel('wrapper-prebuilts', 'panel-prebuilts-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-prebuilts')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        Prebuilts
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-prebuilts', 'panel-prebuilts-heading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Heading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-prebuilts', 'panel-prebuilts-subheading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Subheading
                    </div>

                    @php $prebuilts = $layout['sections'][array_search('prebuilts', array_column($layout['sections'], 'id'))] ?? []; @endphp
                    @php $prebuiltsBlocks = $prebuilts['blocks'] ?? []; @endphp
                    <div id="prebuilts-blocks-container">
                        @foreach($prebuiltsBlocks as $idx => $block)
                        <div class="sub-item prebuilts-block-nav" onclick="openRightPanel('wrapper-prebuilts', 'panel-prebuilts-block-{{ $idx }}'); highlightSub(this);" id="nav-prebuilts-block-{{ $idx }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg> <span>Item {{ $idx + 1 }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="sub-item add-section-btn" onclick="addBlock('prebuilts')" style="color:#2c6ecb; padding-left:32px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add item card
                    </div>
                </div>
            </div>
            @elseif($secId === 'categories')
            <!-- Categories Section -->
            <div class="nav-item-wrapper" id="wrapper-categories" draggable="true" data-section-id="categories">
                <div class="nav-item nav-trigger" data-target="panel-categories-main" onclick="openRightPanel('wrapper-categories', 'panel-categories-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-categories')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        Categories
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-categories', 'panel-categories-heading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Heading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-categories', 'panel-categories-subheading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Subheading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-categories', 'panel-categories-card-1'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg> Card 1
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-categories', 'panel-categories-card-2'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg> Card 2
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-categories', 'panel-categories-card-3'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg> Card 3
                    </div>
                </div>
            </div>
            @elseif($secId === 'cta')
            <!-- CTA Section -->
            <div class="nav-item-wrapper" id="wrapper-cta" draggable="true" data-section-id="cta">
                <div class="nav-item nav-trigger" data-target="panel-cta-main" onclick="openRightPanel('wrapper-cta', 'panel-cta-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-cta')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        CTA Banner
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-cta', 'panel-cta-tag'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Tagline
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-cta', 'panel-cta-heading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Heading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-cta', 'panel-cta-subheading'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Subheading
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-cta', 'panel-cta-buttons'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Buttons
                    </div>
                </div>
            </div>
            @endif
        @endforeach
        </div> <!-- End sortable-sections -->
        @elseif(in_array($context, ['collections', 'categories/category1', 'categories/category2', 'categories/category3', 'store/accessories', 'store/monitors', 'store/pc-parts']))
        @php
            $targetSlug = $context;
            $collectionsPage = collect($customPages)->firstWhere('slug', $targetSlug) ?? [];
            $collectionsPageIndex = array_search($collectionsPage['id'] ?? $targetSlug, array_column($customPages, 'id'));
            if ($collectionsPageIndex === false) {
                $collectionsPageIndex = count($customPages);
            }
        @endphp
        <div id="collections-template-sections">
            <!-- Banner Section -->
            <div class="nav-item-wrapper" id="wrapper-collections-hero">
                <div class="nav-item nav-trigger" data-target="panel-collections-hero-main" onclick="openRightPanel('wrapper-collections-hero', 'panel-collections-hero-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-collections-hero')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                        Banner
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-hero', 'panel-collections-hero-title'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Page Title
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-hero', 'panel-collections-hero-subtitle'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Page Subtitle
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-hero', 'panel-collections-hero-bg'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Background Image
                    </div>
                </div>
            </div>

            @if($context === 'collections')
            <!-- Category Buttons Section -->
            <div class="nav-item-wrapper" id="wrapper-collections-buttons">
                <div class="nav-item nav-trigger" data-target="panel-collections-button-0" onclick="openRightPanel('wrapper-collections-buttons', 'panel-collections-button-0')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-collections-buttons')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                        Category Buttons
                    </div>
                </div>
                <div class="sub-items">
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-buttons', 'panel-collections-button-0'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Button 1
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-buttons', 'panel-collections-button-1'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Button 2
                    </div>
                    <div class="sub-item" onclick="openRightPanel('wrapper-collections-buttons', 'panel-collections-button-2'); highlightSub(this);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Button 3
                    </div>
                </div>
            </div>
            @endif

            <!-- Product Catalog Section -->
            <div class="nav-item-wrapper" id="wrapper-collections-grid">
                <div class="nav-item nav-trigger" data-target="panel-collections-grid-main" onclick="openRightPanel('wrapper-collections-grid', 'panel-collections-grid-main')">
                    <div class="nav-item-left">
                        <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-collections-grid')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Product Catalog
                    </div>
                </div>
                <div class="sub-items">
                    @php
                        $collectionsBlocks = $collectionsPage['blocks'] ?? [];
                        if (empty($collectionsBlocks)) {
                            // Ensure 1 default modifiable card item is present
                            $firstListing = $availableListings->first();
                            $collectionsBlocks = [['listing_id' => (string)($firstListing->id ?? '')]];
                        }
                    @endphp
                    <div id="collections-blocks-container">
                        @foreach($collectionsBlocks as $idx => $block)
                        <div class="sub-item collections-block-nav" onclick="openRightPanel('wrapper-collections-grid', 'panel-collections-block-{{ $idx }}'); highlightSub(this);" id="nav-collections-block-{{ $idx }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg> <span>Item {{ $idx + 1 }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="sub-item add-section-btn" onclick="addBlock('collections')" style="color:#2c6ecb; padding-left:32px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add item
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($isSupportPage || $isCompanyPage)
        @php $currentSP = $supportPagesData[$context] ?? []; @endphp
        <!-- Page Heading Section -->
        <div class="nav-item-wrapper" id="wrapper-support-heading-{{ $context }}">
            <div class="nav-item nav-trigger" data-target="panel-support-heading-{{ $context }}" onclick="openRightPanel('wrapper-support-heading-{{ $context }}', 'panel-support-heading-{{ $context }}')">
                <div class="nav-item-left">
                    <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-support-heading-{{ $context }}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg>
                    Page Heading
                </div>
            </div>
            <div class="sub-items">
                <div class="sub-item" onclick="openRightPanel('wrapper-support-heading-{{ $context }}', 'panel-support-heading-{{ $context }}'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg> Title & Subtitle
                </div>
            </div>
        </div>            @if($context === 'about')
        <div class="nav-item-wrapper" id="wrapper-support-story">
            <div class="nav-item nav-trigger" data-target="panel-support-story" onclick="openRightPanel('wrapper-support-story', 'panel-support-story')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Story & Values
                </div>
            </div>
        </div>
        @endif
        @if($context === 'contact')
        <div class="nav-item-wrapper" id="wrapper-support-contact-cards">
            <div class="nav-item nav-trigger" data-target="panel-support-contact-cards" onclick="openRightPanel('wrapper-support-contact-cards', 'panel-support-contact-cards')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Contact Cards
                </div>
            </div>
        </div>
        @endif
        @if($context === 'contact')
        <div class="nav-item-wrapper" id="wrapper-support-faq-items">
            <div class="nav-item nav-trigger" data-target="panel-support-faq-items" onclick="openRightPanel('wrapper-support-faq-items', 'panel-support-faq-items')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    FAQ Items
                </div>
            </div>
        </div>
        @endif
        @if($context === 'shipping')
        <div class="nav-item-wrapper" id="wrapper-support-rates">
            <div class="nav-item nav-trigger" data-target="panel-support-rates" onclick="openRightPanel('wrapper-support-rates', 'panel-support-rates')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/></svg>
                    Shipping Rates
                </div>
            </div>
        </div>
        <div class="nav-item-wrapper" id="wrapper-support-processing">
            <div class="nav-item nav-trigger" data-target="panel-support-processing" onclick="openRightPanel('wrapper-support-processing', 'panel-support-processing')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Processing Time
                </div>
            </div>
        </div>
        <div class="nav-item-wrapper" id="wrapper-support-tracking">
            <div class="nav-item nav-trigger" data-target="panel-support-tracking" onclick="openRightPanel('wrapper-support-tracking', 'panel-support-tracking')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/></svg>
                    Order Tracking
                </div>
            </div>
        </div>
        @endif
        @if($context === 'returns')
        <div class="nav-item-wrapper" id="wrapper-support-warranty">
            <div class="nav-item nav-trigger" data-target="panel-support-warranty" onclick="openRightPanel('wrapper-support-warranty', 'panel-support-warranty')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Warranty Coverage
                </div>
            </div>
        </div>
        <div class="nav-item-wrapper" id="wrapper-support-policy">
            <div class="nav-item nav-trigger" data-target="panel-support-policy" onclick="openRightPanel('wrapper-support-policy', 'panel-support-policy')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    Return Policy
                </div>
            </div>
        </div>
        <div class="nav-item-wrapper" id="wrapper-support-process">
            <div class="nav-item nav-trigger" data-target="panel-support-process" onclick="openRightPanel('wrapper-support-process', 'panel-support-process')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="12" y1="12" x2="15" y2="15"/><line x1="12" y1="12" x2="9" y2="9"/></svg>
                    Return Process
                </div>
            </div>
        </div>
        @endif
        @if($context === 'careers')
        <div class="nav-item-wrapper" id="wrapper-careers-body">
            <div class="nav-item nav-trigger" data-target="panel-careers-body" onclick="openRightPanel('wrapper-careers-body', 'panel-careers-body')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Page Body
                </div>
            </div>
        </div>
        @endif
        @if($context === 'affiliates')
        <div class="nav-item-wrapper" id="wrapper-affiliates-body">
            <div class="nav-item nav-trigger" data-target="panel-affiliates-body" onclick="openRightPanel('wrapper-affiliates-body', 'panel-affiliates-body')">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Page Body & Benefits
                </div>
            </div>
        </div>
        @endif
        @endif

        <hr style="border: 0; border-top: 1px solid #e1e3e5; margin: 16px 0 0;">

        <div class="sidebar-group-title">Footer</div>
        <div class="nav-item-wrapper" id="wrapper-footer">
            <div class="nav-item nav-trigger" data-target="panel-footer-main" onclick="openRightPanel('wrapper-footer', 'panel-footer-main')">
                <div class="nav-item-left">
                    <span class="chevron-toggle" onclick="toggleExpand(event, 'wrapper-footer')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
                    Footer
                </div>
            </div>
            <div class="sub-items">
                <div class="sub-item" onclick="openRightPanel('wrapper-footer', 'panel-footer-brand'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> Brand Bio
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-footer', 'panel-footer-columns'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg> Column Headers
                </div>
                <div class="sub-item" onclick="openRightPanel('wrapper-footer', 'panel-footer-shop-links'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Shop Links
                </div>

                <div class="sub-item" onclick="openRightPanel('wrapper-footer', 'panel-footer-social'); highlightSub(this);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg> Social Links & Copyright
                </div>
            </div>
        </div>
    </div>
    <!-- Center Preview Iframe -->
    <div class="builder-preview" id="preview-container">
        <div class="builder-preview-inner">
            <iframe id="preview-frame" src="{{ $previewUrl }}"></iframe>
        </div>
    </div>

    <!-- Right Sidebar (Properties Panels) -->
    <div class="builder-right-sidebar" id="right-sidebar">

        <!-- Site Settings Panels -->
        <div class="right-panel-content" id="panel-sitesettings-main">
            <div class="panel-header">
                Site Settings
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62;">Select a tab on the left to edit site settings.</p>
            </div>
        </div>
        <div class="right-panel-content" id="panel-sitesettings-storename">
            <div class="panel-header">
                Tab Store Name
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Store name<input name="brand_name" value="{{ old('brand_name', $layout['brand_name'] ?? '') }}" class="live-input"></label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-sitesettings-tagline">
            <div class="panel-header">
                Tab Tagline
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Tagline<input name="tagline" value="{{ old('tagline', $layout['tagline'] ?? '') }}" class="live-input"></label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-sitesettings-colors">
            <div class="panel-header">
                Site Color Scheme
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content" style="padding: 16px;">
                <p style="font-size: 12px; color: #5c5f62; margin-bottom: 16px;">Select a curated theme preset or customize primary and accent brand colors below.</p>

                <!-- Live Color Pair Preview Card -->
                <div style="margin-bottom: 20px; padding: 14px; background: #0f172a; border-radius: 8px; border: 1px solid #1e293b; color: #fff;">
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 8px;">Theme Pair Preview</div>
                    <div id="color-preview-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 6px; background: linear-gradient(135deg, {{ old('primary_color', $layout['primary_color'] ?? '#ff6b00') }}, {{ old('accent_color', $layout['accent_color'] ?? '#f59e0b') }}); transition: all 0.3s ease;">
                        <span style="font-size: 12px; font-weight: 700; color: #ffffff; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Brand Gradient</span>
                        <span id="preview-badge-accent" style="font-size: 10px; font-weight: 800; background: rgba(0,0,0,0.4); color: #fff; padding: 3px 8px; border-radius: 9999px; text-transform: uppercase;">Active</span>
                    </div>
                </div>

                <!-- Theme Presets Grid -->
                <div class="property-group-title" style="padding: 0; margin-bottom: 10px; border: none; font-size: 13px; font-weight: 600;">Theme Presets</div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 20px;">
                    @php
                        $presets = [
                            ['name' => 'Techforge Orange', 'primary' => '#ff6b00', 'accent' => '#f59e0b'],
                            ['name' => 'Cyber Neon', 'primary' => '#ec4899', 'accent' => '#8b5cf6'],
                            ['name' => 'Electric Violet', 'primary' => '#7c3aed', 'accent' => '#3b82f6'],
                            ['name' => 'Emerald Tech', 'primary' => '#10b981', 'accent' => '#06b6d4'],
                            ['name' => 'Sapphire Blue', 'primary' => '#2563eb', 'accent' => '#60a5fa'],
                            ['name' => 'Crimson Fire', 'primary' => '#ef4444', 'accent' => '#f97316'],
                            ['name' => 'Gold Luxury', 'primary' => '#eab308', 'accent' => '#f97316'],
                            ['name' => 'Midnight Purple', 'primary' => '#6366f1', 'accent' => '#a855f7'],
                        ];
                    @endphp
                    @foreach($presets as $p)
                    <button type="button" class="color-preset-btn" onclick="applyColorPreset('{{ $p['primary'] }}', '{{ $p['accent'] }}')" style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; text-align: left;" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'">
                        <div style="display: flex; width: 24px; height: 24px; border-radius: 50%; overflow: hidden; border: 1px solid rgba(0,0,0,0.1); shrink-0;">
                            <div style="width: 50%; height: 100%; background: {{ $p['primary'] }};"></div>
                            <div style="width: 50%; height: 100%; background: {{ $p['accent'] }};"></div>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; color: #334155; line-height: 1.2;">{{ $p['name'] }}</span>
                    </button>
                    @endforeach
                </div>

                <!-- Custom Color Pickers -->
                <div class="property-group-title" style="padding: 0; margin-bottom: 10px; border: none; font-size: 13px; font-weight: 600;">Custom Colors</div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">Primary Color</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" id="primary-color-picker" name="primary_color" value="{{ old('primary_color', $layout['primary_color'] ?? '#ff6b00') }}" class="live-input" style="width: 40px; height: 38px; padding: 2px; border: 1px solid #c9cccf; border-radius: 6px; cursor: pointer; background: #fff;">
                        <input type="text" id="primary-color-hex" value="{{ old('primary_color', $layout['primary_color'] ?? '#ff6b00') }}" oninput="syncColorInput('primary-color-picker', this.value)" style="flex: 1; padding: 8px 12px; font-size: 13px; font-family: monospace; text-transform: uppercase; border: 1px solid #c9cccf; border-radius: 6px;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">Accent Color</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" id="accent-color-picker" name="accent_color" value="{{ old('accent_color', $layout['accent_color'] ?? '#f59e0b') }}" class="live-input" style="width: 40px; height: 38px; padding: 2px; border: 1px solid #c9cccf; border-radius: 6px; cursor: pointer; background: #fff;">
                        <input type="text" id="accent-color-hex" value="{{ old('accent_color', $layout['accent_color'] ?? '#f59e0b') }}" oninput="syncColorInput('accent-color-picker', this.value)" style="flex: 1; padding: 8px 12px; font-size: 13px; font-family: monospace; text-transform: uppercase; border: 1px solid #c9cccf; border-radius: 6px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Header / Navbar Panels -->
        <div class="right-panel-content" id="panel-header-main">
            <div class="panel-header">
                Navbar
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 12px;">Configure the navbar announcement bar, logo, and store name.</p>
                
                <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e1e3e5;">
                    <label style="display:flex; align-items:center; gap:8px; font-weight: 600;">
                        <input type="checkbox" name="announcement_enabled" style="width:auto;" @checked(old('announcement_enabled', $navbar['announcement_enabled'] ?? true)) class="live-input"> Enable Announcement Bar
                    </label>
                    <label style="margin-top: 12px;">Announcement Text
                        <input name="announcement_text" value="{{ old('announcement_text', $navbar['announcement_text'] ?? '🔥 Free shipping on all orders over ₱50,000!') }}" class="live-input">
                    </label>
                    <label style="margin-top: 12px;">Announcement Link URL
                        <input name="announcement_url" value="{{ old('announcement_url', $navbar['announcement_url'] ?? '') }}" placeholder="e.g. /products or #listings" class="live-input">
                    </label>
                </div>

                <label style="margin-top: 16px;">Store Name
                    <input name="brand_name" value="{{ old('brand_name', $layout['brand_name'] ?? '') }}" class="live-input">
                </label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-header-logo">
            <div class="panel-header">
                Logo
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; color: #202223; font-weight: 500;">Store Logo</span>
                    @if(!empty($layout['logo_path']))
                    <label class="remove-logo-label" style="font-size: 11px; font-weight: 500; color: #d8000c; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                        <input type="checkbox" name="remove_logo" value="1" style="width: auto; margin: 0;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Remove Logo
                    </label>
                    @endif
                </div>
                <input type="file" name="logo" accept="image/*" class="live-input" style="margin-top: 4px;">
                @php
                    $brandNameH = old('brand_name', $layout['brand_name'] ?? '');
                    $logoUrlH = !empty($layout['logo_path']) ? (str_starts_with($layout['logo_path'], 'Modules/') ? Vite::asset($layout['logo_path']) : asset('storage/'.$layout['logo_path'])) : null;
                @endphp
                <div class="logo-preview-card" style="margin-top: 12px; border: 1px solid #e1e3e5; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                    <div class="logo-preview-inner" style="background: #0f172a; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 72px;">
                        <div class="logo-preview-row" style="display: flex; align-items: center; gap: 12px;">
                            @if($logoUrlH)
                                <img src="{{ $logoUrlH }}" alt="Logo preview" style="max-height: 36px; object-fit: contain;" class="logo-preview-img">
                            @else
                                <div class="logo-preview-img logo-preview-placeholder" style="width: auto; height: 32px; padding: 0 10px; background: rgba(255,255,255,0.08); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.35); font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">{{ strtoupper($storefrontCompany?->company_name ?? $brandNameH ?: 'STORE') }} LOGO</div>
                            @endif
                            <span class="logo-preview-name" style="color: #fff; font-size: 15px; font-weight: 700; letter-spacing: -0.3px;">{{ $brandNameH ?: 'Your Store Name' }}</span>
                        </div>
                    </div>
                    <div style="padding: 8px 12px; background: #f8f9fb; border-top: 1px solid #e1e3e5; font-size: 11px; color: #6d7175; display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Navbar preview — logo + store name
                    </div>
                </div>
            </div>
        </div>
        <div class="right-panel-content" id="panel-header-name">
            <div class="panel-header">
                Name
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Store Name
                    <input name="brand_name" value="{{ old('brand_name', $layout['brand_name'] ?? '') }}" class="live-input">
                </label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-header-announcement">
            <div class="panel-header">
                Announcement Bar
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label style="display:flex; align-items:center; gap:8px; font-weight: 600;">
                    <input type="checkbox" name="announcement_enabled" style="width:auto;" @checked(old('announcement_enabled', $navbar['announcement_enabled'] ?? true)) class="live-input"> Enable Announcement Bar
                </label>
                <label style="margin-top: 16px;">Announcement Text
                    <input name="announcement_text" value="{{ old('announcement_text', $navbar['announcement_text'] ?? '🔥 Free shipping on all orders over ₱50,000!') }}" class="live-input">
                </label>
                <label style="margin-top: 16px;">Announcement Link URL
                    <input name="announcement_url" value="{{ old('announcement_url', $navbar['announcement_url'] ?? '') }}" placeholder="e.g. /products or #listings" class="live-input">
                </label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-header-nav-buttons-main">
            <div class="panel-header">
                Secondary Navigation
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #6d7175; margin-bottom: 16px;">Configure the secondary navigation links displayed on the bottom section of the navbar.</p>
            </div>
        </div>

        @php
            $categoryUrls = ['/categories/category1', '/categories/category2', '/categories/category3'];
        @endphp
        @for($i = 0; $i < 3; $i++)
        <div class="right-panel-content" id="panel-header-nav-btn-{{ $i + 1 }}">
            <div class="panel-header">
                Link {{ $i + 1 }}
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Label
                    <input name="navbar[links][{{ $i }}][label]" value="{{ old('navbar.links.'.$i.'.label', $navbar['links'][$i]['label'] ?? '') }}" class="live-input">
                </label>
                <input type="hidden" name="navbar[links][{{ $i }}][url]" value="{{ $categoryUrls[$i] }}">
            </div>
        </div>
        @endfor

        <!-- Hero Panels -->
        <div class="right-panel-content" id="panel-hero-main">
            <div class="panel-header">
                Hero
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="hero_enabled" style="width:auto;" @checked(old('hero_enabled', $hero['enabled'] ?? false)) class="live-input"> Visible</label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-hero-heading">
            <div class="panel-header">
                Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'hero_title', 'label' => 'Headline', 'value' => old('hero_title', $hero['title'] ?? '')])
            </div>

            <div class="property-group-title">Layout</div>
            <div class="section-content" style="padding-top: 8px;">
                <label>Width
                    <select name="hero_title_width" class="live-input">
                        <option value="auto" @selected(old('hero_title_width', $hero['title_width'] ?? 'auto') === 'auto')>Auto</option>
                        <option value="full" @selected(old('hero_title_width', $hero['title_width'] ?? 'auto') === 'full')>Full Width</option>
                        <option value="narrow" @selected(old('hero_title_width', $hero['title_width'] ?? 'auto') === 'narrow')>Narrow</option>
                    </select>
                </label>
            </div>

            <div class="property-group-title">Typography</div>
            <div class="section-content" style="padding-top: 8px;">
                <label>Preset
                    <select name="hero_title_preset" class="live-input">
                        <option value="h1" @selected(old('hero_title_preset', $hero['title_preset'] ?? 'h1') === 'h1')>Heading 1</option>
                        <option value="h2" @selected(old('hero_title_preset', $hero['title_preset'] ?? 'h1') === 'h2')>Heading 2</option>
                        <option value="h3" @selected(old('hero_title_preset', $hero['title_preset'] ?? 'h1') === 'h3')>Heading 3</option>
                        <option value="body" @selected(old('hero_title_preset', $hero['title_preset'] ?? 'h1') === 'body')>Body text</option>
                    </select>
                </label>
            </div>

            <div class="property-group-title">Appearance</div>
            <div class="section-content" style="padding-top: 8px;">
                <label>Text Color<input type="color" name="hero_title_color" value="{{ old('hero_title_color', $hero['title_color'] ?? '#ffffff') }}" style="height:32px; padding:0;" class="live-input"></label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-hero-subheading">
            <div class="panel-header">
                Subheading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'hero_body', 'label' => 'Description', 'value' => old('hero_body', $hero['body'] ?? '')])
            </div>
        </div>

        <div class="right-panel-content" id="panel-hero-button">
            <div class="panel-header">
                Button
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @php $heroBtn = $hero['buttons'][0] ?? ['label' => '', 'url' => '#products']; @endphp
                <label>Button Label<input name="hero_buttons[0][label]" value="{{ old('hero_buttons.0.label', $heroBtn['label'] ?? '') }}" class="live-input"></label>
                <label>Button URL<input name="hero_buttons[0][url]" value="{{ old('hero_buttons.0.url', $heroBtn['url'] ?? '') }}" class="live-input"></label>
                <label>CTA Subtext<input name="hero_cta_subtext" value="{{ old('hero_cta_subtext', $hero['cta_subtext'] ?? '') }}" class="live-input"></label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-hero-stats">
            <div class="panel-header">
                Stats Row
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @for($i = 0; $i < 3; $i++)
                <div style="margin-bottom: 12px; padding: 12px; border: 1px solid #e1e3e5; border-radius: 4px;">
                    <div style="font-size: 12px; font-weight: 600; margin-bottom: 8px;">Stat {{ $i + 1 }}</div>
                    <label>Value (e.g. 4,200+)<input name="hero_stats[{{ $i }}][value]" value="{{ old('hero_stats.'.$i.'.value', $hero['hero_stats'][$i]['value'] ?? '') }}" class="live-input" style="margin-bottom: 8px;"></label>
                    <label>Label (e.g. Units Shipped)<input name="hero_stats[{{ $i }}][label]" value="{{ old('hero_stats.'.$i.'.label', $hero['hero_stats'][$i]['label'] ?? '') }}" class="live-input"></label>
                </div>
                @endfor
            </div>
        </div>

        <div class="right-panel-content" id="panel-hero-image">
            <div class="panel-header">
                Main Image &amp; Gallery
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>

            <div class="property-group-title">Featured Listings</div>
            <div class="section-content" style="padding-top: 8px;">
                <p style="font-size: 12px; color: #5c5f62; margin-bottom: 12px;">Select up to 4 listings to display as gallery thumbnails. The first item's image appears as the main hero image.</p>
                @php $featuredConfigs = $hero['featured_configs'] ?? []; @endphp
                @for ($i = 0; $i < 4; $i++)
                <label>Featured Item {{ $i + 1 }}
                    <select name="hero_featured_configs[{{ $i }}]" class="live-input" style="margin-bottom: 8px;">
                        <option value="">â€” None â€”</option>
                        @foreach($availableListings as $listing)
                            <option value="{{ $listing->id }}" @selected(isset($featuredConfigs[$i]) && (string)$featuredConfigs[$i] === (string)$listing->id)>{{ $listing->name }}</option>
                        @endforeach
                    </select>
                </label>
                @endfor

                <label>Badge Text<input type="text" name="hero_badge_text" value="{{ old('hero_badge_text', $hero['badge_text'] ?? 'FEATURED BUILD') }}" class="live-input" placeholder="FEATURED BUILD"></label>
                <label>Gallery Cycle Speed (seconds)<input type="number" name="hero_gallery_cycle" value="{{ old('hero_gallery_cycle', $hero['gallery_cycle'] ?? 5) }}" class="live-input" min="1" max="60"></label>
            </div>

            <div class="property-group-title">Appearance</div>
            <div class="section-content" style="padding-top: 8px;">
                <label>Overlay Opacity (%)<input type="number" name="hero_overlay_opacity" value="{{ old('hero_overlay_opacity', $hero['overlay_opacity'] ?? 0) }}" class="live-input" min="0" max="100"></label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-hero-marquee">
            <div class="panel-header">
                Features Marquee
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Edit the scrolling text items at the bottom of the hero section.</p>
                @for($i = 0; $i < 6; $i++)
                <label>Item {{ $i + 1 }}<input name="hero_marquee[{{ $i }}][text]" value="{{ old('hero_marquee.'.$i.'.text', $hero['hero_marquee'][$i]['text'] ?? '') }}" class="live-input" style="margin-bottom: 8px;"></label>
                @endfor
            </div>
        </div>

        <!-- Tiers Panels -->
        <div class="right-panel-content" id="panel-tiers-main">
            <div class="panel-header">
                Tiers
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @php $tiers = $layout['sections'][array_search('tiers', array_column($layout['sections'], 'id'))] ?? []; @endphp
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="tiers_enabled" style="width:auto;" @checked(old('tiers_enabled', $tiers['enabled'] ?? false)) class="live-input"> Visible</label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-tiers-heading">
            <div class="panel-header">
                Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'tiers_title', 'label' => 'Text', 'value' => old('tiers_title', $tiers['title'] ?? "Select\nYour Tier")])
            </div>
        </div>
        <div class="right-panel-content" id="panel-tiers-subheading">
            <div class="panel-header">
                Subheading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'tiers_body', 'label' => 'Description', 'value' => old('tiers_body', $tiers['body'] ?? 'Four configurations. Every one tested under load for 72 hours before it leaves our facility.')])
            </div>
        </div>
        <div id="tiers-blocks-panels">
            @foreach($tiersBlocks as $idx => $block)
            <div class="right-panel-content tiers-block-panel" id="panel-tiers-block-{{ $idx }}">
                <div class="panel-header">
                    <span>Item {{ $idx + 1 }}</span>
                    <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="section-content">
                    <label>Select Listing
                        <select name="tiers_blocks[{{ $idx }}][listing_id]" class="live-input">
                            <option value="">-- Select Listing --</option>
                            @foreach($availableListings as $listing)
                                <option value="{{ $listing->id }}" @selected(($block['listing_id'] ?? '') == $listing->id)>{{ $listing->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @include('ecommerce::components.admin.rich-text', ['name' => 'tiers_blocks['.$idx.'][description]', 'label' => 'Description Override', 'value' => old('tiers_blocks.'.$idx.'.description', $block['description'] ?? '')])
                    <p style="font-size: 11px; color: #5c5f62; margin-top: 4px; margin-bottom: 12px;">Leave blank to use the listing's own description.</p>
                    <div style="display:flex; gap:8px; margin-top:16px;">
                        <button type="button" onclick="duplicateBlock('tiers', {{ $idx }})" style="flex:1; padding:8px; border:1px solid #e1e3e5; color:#202223; background:transparent; border-radius:4px; cursor:pointer;">Duplicate</button>
                        @if($idx >= 4)
                        <button type="button" onclick="removeBlock('tiers', {{ $idx }})" style="flex:1; padding:8px; border:1px solid #ff4d4d; color:#ff4d4d; background:transparent; border-radius:4px; cursor:pointer;">Remove</button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Prebuilts Panels -->
        <div class="right-panel-content" id="panel-prebuilts-main">
            <div class="panel-header">
                Prebuilts
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @php $prebuilts = $layout['sections'][array_search('prebuilts', array_column($layout['sections'], 'id'))] ?? []; @endphp
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="prebuilts_enabled" style="width:auto;" @checked(old('prebuilts_enabled', $prebuilts['enabled'] ?? false)) class="live-input"> Visible</label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-prebuilts-heading">
            <div class="panel-header">
                Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'prebuilts_title', 'label' => 'Text', 'value' => old('prebuilts_title', $prebuilts['title'] ?? "Pre-Built\nSystems")])
            </div>
        </div>
        <div class="right-panel-content" id="panel-prebuilts-subheading">
            <div class="panel-header">
                Subheading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'prebuilts_body', 'label' => 'Description', 'value' => old('prebuilts_body', $prebuilts['body'] ?? 'Ready to ship. Professionally assembled and stress-tested for out-of-the-box performance.')])
            </div>
        </div>
        <div id="prebuilts-blocks-panels">
            @foreach($prebuiltsBlocks as $idx => $block)
            <div class="right-panel-content prebuilts-block-panel" id="panel-prebuilts-block-{{ $idx }}">
                <div class="panel-header">
                    <span>Item {{ $idx + 1 }}</span>
                    <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="section-content">
                    <label>Select Listing
                        <select name="prebuilts_blocks[{{ $idx }}][listing_id]" class="live-input">
                            <option value="">-- Select Listing --</option>
                            @foreach($availableListings as $listing)
                                <option value="{{ $listing->id }}" @selected(($block['listing_id'] ?? '') == $listing->id)>{{ $listing->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @include('ecommerce::components.admin.rich-text', ['name' => 'prebuilts_blocks['.$idx.'][description]', 'label' => 'Description Override', 'value' => old('prebuilts_blocks.'.$idx.'.description', $block['description'] ?? '')])
                    <p style="font-size: 11px; color: #5c5f62; margin-top: 4px; margin-bottom: 12px;">Leave blank to use the listing's own description.</p>
                    <div style="display:flex; gap:8px; margin-top:16px;">
                        <button type="button" onclick="duplicateBlock('prebuilts', {{ $idx }})" style="flex:1; padding:8px; border:1px solid #e1e3e5; color:#202223; background:transparent; border-radius:4px; cursor:pointer;">Duplicate</button>
                        @if($idx >= 4)
                        <button type="button" onclick="removeBlock('prebuilts', {{ $idx }})" style="flex:1; padding:8px; border:1px solid #ff4d4d; color:#ff4d4d; background:transparent; border-radius:4px; cursor:pointer;">Remove</button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Categories Panels -->
        <div class="right-panel-content" id="panel-categories-main">
            <div class="panel-header">
                Categories
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @php $categories = $layout['sections'][array_search('categories', array_column($layout['sections'], 'id'))] ?? []; @endphp
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="categories_enabled" style="width:auto;" @checked(old('categories_enabled', $categories['enabled'] ?? false)) class="live-input"> Visible</label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-categories-heading">
            <div class="panel-header">
                Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'categories_title', 'label' => 'Text', 'value' => old('categories_title', $categories['title'] ?? "Explore\nCategories")])
            </div>
        </div>
        <div class="right-panel-content" id="panel-categories-subheading">
            <div class="panel-header">
                Subheading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'categories_body', 'label' => 'Description', 'value' => old('categories_body', $categories['body'] ?? 'Find exactly what you need. From ready-to-ship systems to fully custom workstations.')])
            </div>
        </div>

        @php
            $catBlocks = $categories['blocks'] ?? [];
            $defaultCatBlocks = [
                ['title' => 'Category Showcase 1', 'description' => 'Browse our first curated category of products.', 'image' => 'https://images.unsplash.com/photo-1547082299-de196ea013d6?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Category Showcase 2', 'description' => 'Discover featured products in our second category.', 'image' => 'https://images.unsplash.com/photo-1618339220157-daa2cd9ade56?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Category Showcase 3', 'description' => 'Explore our third category selection.', 'image' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=800&q=80'],
            ];
        @endphp
        @for($ci = 0; $ci < 3; $ci++)
        <div class="right-panel-content" id="panel-categories-card-{{ $ci + 1 }}">
            <div class="panel-header">
                Card {{ $ci + 1 }}
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Image URL
                    <input name="categories[blocks][{{ $ci }}][image]" value="{{ old('categories.blocks.'.$ci.'.image', $catBlocks[$ci]['image'] ?? $defaultCatBlocks[$ci]['image']) }}" class="live-input live-cat-img" data-cat-index="{{ $ci }}">
                </label>
                <label>Title
                    <input name="categories[blocks][{{ $ci }}][title]" value="{{ old('categories.blocks.'.$ci.'.title', $catBlocks[$ci]['title'] ?? $defaultCatBlocks[$ci]['title']) }}" class="live-input live-cat-title" data-cat-index="{{ $ci }}">
                </label>
                <label>Description
                    <textarea name="categories[blocks][{{ $ci }}][description]" class="live-input live-cat-desc" data-cat-index="{{ $ci }}" rows="2">{{ old('categories.blocks.'.$ci.'.description', $catBlocks[$ci]['description'] ?? $defaultCatBlocks[$ci]['description']) }}</textarea>
                </label>
            </div>
        </div>
        @endfor

        <!-- CTA Panels -->
        <div class="right-panel-content" id="panel-cta-main">
            <div class="panel-header">
                CTA Banner
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @php $cta = $layout['sections'][array_search('cta', array_column($layout['sections'], 'id'))] ?? []; @endphp
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="cta_enabled" style="width:auto;" @checked(old('cta_enabled', $cta['enabled'] ?? false)) class="live-input"> Visible</label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-cta-tag">
            <div class="panel-header">
                Tagline
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Tag text<input name="cta_tag_text" value="{{ old('cta_tag_text', $cta['tag_text'] ?? 'READY_TO_BUILD') }}" class="live-input"></label>
            </div>
        </div>
        <div class="right-panel-content" id="panel-cta-heading">
            <div class="panel-header">
                Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'cta_title', 'label' => 'Title', 'value' => old('cta_title', $cta['title'] ?? 'Stop Settling.')])
                @include('ecommerce::components.admin.rich-text', ['name' => 'cta_subtitle', 'label' => 'Subtitle', 'value' => old('cta_subtitle', $cta['subtitle'] ?? 'Start Winning.')])
            </div>
        </div>
        <div class="right-panel-content" id="panel-cta-subheading">
            <div class="panel-header">
                Subheading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => 'cta_body', 'label' => 'Description', 'value' => old('cta_body', $cta['body'] ?? 'Free shipping. Free setup support. 30-day no-questions return policy. Your next machine is three clicks away.')])
            </div>
        </div>
        <div class="right-panel-content" id="panel-cta-buttons">
            <div class="panel-header">
                Buttons
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Primary Button Label<input name="cta_primary_button_label" value="{{ old('cta_primary_button_label', $cta['primary_button_label'] ?? 'Build Yours Now') }}" class="live-input"></label>
                <label>Primary Button URL<input name="cta_primary_button_url" value="{{ old('cta_primary_button_url', $cta['primary_button_url'] ?? '/configurator') }}" class="live-input"></label>
                <div style="margin-top: 16px;"></div>
                <label>Secondary Button Label<input name="cta_secondary_button_label" value="{{ old('cta_secondary_button_label', $cta['secondary_button_label'] ?? 'Talk To An Expert') }}" class="live-input"></label>
                <label>Secondary Button URL<input name="cta_secondary_button_url" value="{{ old('cta_secondary_button_url', $cta['secondary_button_url'] ?? '/contact') }}" class="live-input"></label>
            </div>
        </div>

        <!-- Footer Panels -->
        <div class="right-panel-content" id="panel-footer-main">
            <div class="panel-header">
                Footer Overview
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Configure your storefront footer bio, column headings, copyright, and social media URLs.</p>
                <label>Footer Description / Bio
                    <textarea name="footer_description" rows="3" class="live-input" style="margin-top: 4px;">{{ old('footer_description', $footer['description'] ?? 'Performance-driven computers and accessories for every digital journey.') }}</textarea>
                </label>
                <label style="margin-top: 16px;">Column 1 Title (Shop)
                    <input name="footer_column_1_title" value="{{ old('footer_column_1_title', $footer['column_1_title'] ?? 'Shop') }}" class="live-input">
                </label>
                <label style="margin-top: 12px;">Column 2 Title (Support)
                    <input name="footer_column_2_title" value="{{ old('footer_column_2_title', $footer['column_2_title'] ?? 'Support') }}" class="live-input">
                </label>
                <label style="margin-top: 12px;">Column 3 Title (Company)
                    <input name="footer_column_3_title" value="{{ old('footer_column_3_title', $footer['column_3_title'] ?? 'Company') }}" class="live-input">
                </label>
                <label style="margin-top: 16px;">Copyright Notice
                    <input name="footer_copyright_text" value="{{ old('footer_copyright_text', $footer['copyright_text'] ?? 'All rights reserved.') }}" class="live-input">
                </label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-footer-brand">
            <div class="panel-header">
                Brand Bio
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Footer Description / Tagline
                    <textarea name="footer_description" rows="4" class="live-input" style="margin-top: 4px;">{{ old('footer_description', $footer['description'] ?? 'Performance-driven computers and accessories for every digital journey.') }}</textarea>
                </label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-footer-columns">
            <div class="panel-header">
                Column Headers
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Column 1 Header (Default: Shop)
                    <input name="footer_column_1_title" value="{{ old('footer_column_1_title', $footer['column_1_title'] ?? 'Shop') }}" class="live-input">
                </label>
                <label style="margin-top: 16px;">Column 2 Header (Default: Support - Static)
                    <input name="footer_column_2_title" value="{{ old('footer_column_2_title', $footer['column_2_title'] ?? 'Support') }}" class="live-input">
                </label>
                <label style="margin-top: 16px;">Column 3 Header (Default: Company)
                    <input name="footer_column_3_title" value="{{ old('footer_column_3_title', $footer['column_3_title'] ?? 'Company') }}" class="live-input">
                </label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-footer-shop-links">
            <div class="panel-header">
                Shop Column Links
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Customize link labels under the Shop column. URLs are fixed: Collections → /collections, Category 1 → /categories/category1, etc.</p>
                @php
                    $sLabels = collect($footer['shop_links'] ?? [])->pluck('label')->values()->all();
                    $defaultLabels = ['Collections', 'Category 1', 'Category 2', 'Category 3'];
                    while(count($sLabels) < 4) $sLabels[] = '';
                @endphp
                @foreach($defaultLabels as $idx => $defaultLabel)
                <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;">
                    <div style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Link #{{ $idx + 1 }} — <code style="font-size: 11px; color: #64748b;">{{ ['/collections', '/categories/category1', '/categories/category2', '/categories/category3'][$idx] }}</code></div>
                    <label style="margin-top:0;">Label
                        <input name="footer_shop_links[{{ $idx }}][label]" value="{{ old("footer_shop_links.{$idx}.label", $sLabels[$idx] ?? $defaultLabel) }}" placeholder="{{ $defaultLabel }}" class="live-input">
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <div class="right-panel-content" id="panel-footer-social">
            <div class="panel-header">
                Social Links & Copyright
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Copyright Text
                    <input name="footer_copyright_text" value="{{ old('footer_copyright_text', $footer['copyright_text'] ?? 'All rights reserved.') }}" class="live-input">
                </label>
                <label style="margin-top: 16px;">Instagram URL
                    <input name="footer_social_instagram" value="{{ old('footer_social_instagram', $footer['social_instagram'] ?? '#') }}" placeholder="https://instagram.com/yourhandle" class="live-input">
                </label>
                <label style="margin-top: 12px;">Twitter / X URL
                    <input name="footer_social_twitter" value="{{ old('footer_social_twitter', $footer['social_twitter'] ?? '#') }}" placeholder="https://x.com/yourhandle" class="live-input">
                </label>
                <label style="margin-top: 12px;">Facebook URL
                    <input name="footer_social_facebook" value="{{ old('footer_social_facebook', $footer['social_facebook'] ?? '#') }}" placeholder="https://facebook.com/yourhandle" class="live-input">
                </label>
                <label style="margin-top: 12px;">YouTube URL
                    <input name="footer_social_youtube" value="{{ old('footer_social_youtube', $footer['social_youtube'] ?? '#') }}" placeholder="https://youtube.com/c/yourhandle" class="live-input">
                </label>
            </div>
        </div>

        <!-- Support Page Properties Panels -->
        @php
            $supportPagesList = ['contact', 'shipping', 'returns'];
            $companyPagesList = ['about', 'careers', 'affiliates'];
        @endphp
        @foreach($supportPagesList as $spPage)
        <div class="right-panel-content" id="panel-support-heading-{{ $spPage }}">
            <div class="panel-header">
                Page Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <div style="margin-bottom: 16px;">
                    <label style="margin-top:0;font-weight:600;font-size:14px;color:#202223;">{{ $supportPagesData[$spPage]['title'] ?? 'Page Title' }}</label>
                    <input type="hidden" name="support_pages[{{ $spPage }}][title]" value="{{ old('support_pages.' . $spPage . '.title', $supportPagesData[$spPage]['title'] ?? '') }}">
                    <p style="font-size:11px;color:#8c9196;margin-top:2px;">Title is fixed — edit subtitle below</p>
                </div>
                <label style="margin-top: 0;">Subtitle
                    <textarea name="support_pages[{{ $spPage }}][subtitle]" rows="2" class="live-input live-input-save">{{ old('support_pages.' . $spPage . '.subtitle', $supportPagesData[$spPage]['subtitle'] ?? '') }}</textarea>
                </label>
            </div>
        </div>
        @endforeach

        @foreach($companyPagesList as $cpPage)
        <div class="right-panel-content" id="panel-support-heading-{{ $cpPage }}">
            <div class="panel-header">
                Page Heading
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <div style="margin-bottom: 16px;">
                    <label style="margin-top:0;font-weight:600;font-size:14px;color:#202223;">{{ $companyPagesData[$cpPage]['title'] ?? 'Page Title' }}</label>
                    <input type="hidden" name="company_pages[{{ $cpPage }}][title]" value="{{ old('company_pages.' . $cpPage . '.title', $companyPagesData[$cpPage]['title'] ?? '') }}">
                    <p style="font-size:11px;color:#8c9196;margin-top:2px;">Title is fixed — edit subtitle below</p>
                </div>
                <label style="margin-top: 0;">Subtitle
                    <textarea name="company_pages[{{ $cpPage }}][subtitle]" rows="2" class="live-input live-input-save">{{ old('company_pages.' . $cpPage . '.subtitle', $companyPagesData[$cpPage]['subtitle'] ?? '') }}</textarea>
                </label>
            </div>
        </div>
        @endforeach

        <!-- About: Story & Values -->
        <div class="right-panel-content" id="panel-support-story">
            <div class="panel-header">
                Story & Values
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Story
                    <textarea name="company_pages[about][story]" rows="6" class="live-input live-input-save">{{ old('company_pages.about.story', $companyPagesData['about']['story'] ?? '') }}</textarea>
                </label>
                <label style="margin-top: 16px;">CTA Label
                    <input name="company_pages[about][cta_label]" value="{{ old('company_pages.about.cta_label', $companyPagesData['about']['cta_label'] ?? 'Browse Store') }}" class="live-input live-input-save">
                </label>
            </div>
        </div>

        <!-- Contact: Contact Cards -->
        <div class="right-panel-content" id="panel-support-contact-cards">
            <div class="panel-header">
                Contact Cards
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['contact']['cards'] ?? []) as $ci => $card)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;">{{ $card['title'] }}</label>
                    <label>Detail
                        <input name="support_pages[contact][cards][{{ $ci }}][detail]" value="{{ $card['detail'] }}" class="live-input live-input-save">
                    </label>
                    <label>Subtitle
                        <input name="support_pages[contact][cards][{{ $ci }}][sub]" value="{{ $card['sub'] }}" class="live-input live-input-save">
                    </label>
                    <input type="hidden" name="support_pages[contact][cards][{{ $ci }}][icon]" value="{{ $card['icon'] }}">
                    <input type="hidden" name="support_pages[contact][cards][{{ $ci }}][title]" value="{{ $card['title'] }}">
                </div>
                @endforeach
            </div>
        </div>

        <!-- FAQ: FAQ Items -->
        <div class="right-panel-content" id="panel-support-faq-items">
            <div class="panel-header">
                FAQ Items
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['contact']['faq_items'] ?? []) as $fi => $item)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;">Q{{ $fi + 1 }}</label>
                    <label>Question
                        <input name="support_pages[contact][faq_items][{{ $fi }}][q]" value="{{ $item['q'] }}" class="live-input live-input-save">
                    </label>
                    <label>Answer
                        <textarea name="support_pages[contact][faq_items][{{ $fi }}][a]" rows="2" class="live-input live-input-save">{{ $item['a'] }}</textarea>
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Shipping: Rates -->
        <div class="right-panel-content" id="panel-support-rates">
            <div class="panel-header">
                Shipping Rates
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['shipping']['rates'] ?? []) as $ri => $rate)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;">{{ $rate['label'] }}</label>
                    <label>Description
                        <input name="support_pages[shipping][rates][{{ $ri }}][desc]" value="{{ $rate['desc'] }}" class="live-input live-input-save">
                    </label>
                    <label>Price
                        <input name="support_pages[shipping][rates][{{ $ri }}][price]" value="{{ $rate['price'] }}" class="live-input live-input-save">
                    </label>
                    <input type="hidden" name="support_pages[shipping][rates][{{ $ri }}][label]" value="{{ $rate['label'] }}">
                    <input type="hidden" name="support_pages[shipping][rates][{{ $ri }}][highlighted]" value="{{ $rate['highlighted'] ? '1' : '0' }}">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Shipping: Processing Time -->
        <div class="right-panel-content" id="panel-support-processing">
            <div class="panel-header">
                Processing Time
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['shipping']['processing'] ?? []) as $pi => $proc)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;" class="live-input">{{ $proc['label'] }}</label>
                    <label>Description
                        <input name="support_pages[shipping][processing][{{ $pi }}][desc]" value="{{ $proc['desc'] }}" class="live-input live-input-save">
                    </label>
                    <input type="hidden" name="support_pages[shipping][processing][{{ $pi }}][label]" value="{{ $proc['label'] }}">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Shipping: Order Tracking -->
        <div class="right-panel-content" id="panel-support-tracking">
            <div class="panel-header">
                Order Tracking
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Tracking Description
                    <textarea name="support_pages[shipping][tracking_body]" rows="3" class="live-input live-input-save">{{ old('support_pages.shipping.tracking_body', $supportPagesData['shipping']['tracking_body'] ?? '') }}</textarea>
                </label>
            </div>
        </div>

        <!-- Returns: Warranty -->
        <div class="right-panel-content" id="panel-support-warranty">
            <div class="panel-header">
                Warranty Coverage
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Warranty Title
                    <input name="support_pages[returns][warranty_title]" value="{{ old('support_pages.returns.warranty_title', $supportPagesData['returns']['warranty_title'] ?? 'Warranty Coverage') }}" class="live-input live-input-save">
                </label>
                <label style="margin-top: 12px;">Subtitle
                    <input name="support_pages[returns][warranty_sub]" value="{{ old('support_pages.returns.warranty_sub', $supportPagesData['returns']['warranty_sub'] ?? '') }}" class="live-input live-input-save">
                </label>
                <label style="margin-top: 12px;">Body
                    <textarea name="support_pages[returns][warranty_body]" rows="3" class="live-input live-input-save">{{ old('support_pages.returns.warranty_body', $supportPagesData['returns']['warranty_body'] ?? '') }}</textarea>
                </label>
            </div>
        </div>

        <!-- Returns: Return Policy -->
        <div class="right-panel-content" id="panel-support-policy">
            <div class="panel-header">
                Return Policy
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['returns']['policy'] ?? []) as $pi => $pItem)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;">{{ $pItem['title'] }}</label>
                    <label>Description
                        <textarea name="support_pages[returns][policy][{{ $pi }}][desc]" rows="2" class="live-input live-input-save">{{ $pItem['desc'] }}</textarea>
                    </label>
                    <input type="hidden" name="support_pages[returns][policy][{{ $pi }}][icon]" value="{{ $pItem['icon'] }}">
                    <input type="hidden" name="support_pages[returns][policy][{{ $pi }}][color]" value="{{ $pItem['color'] }}">
                    <input type="hidden" name="support_pages[returns][policy][{{ $pi }}][title]" value="{{ $pItem['title'] }}">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Returns: Return Process Steps -->
        <div class="right-panel-content" id="panel-support-process">
            <div class="panel-header">
                Return Process
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @foreach(($supportPagesData['returns']['steps'] ?? []) as $si => $step)
                <div style="border:1px solid #e1e3e5;border-radius:8px;padding:12px;margin-bottom:12px;">
                    <label style="margin-top:0;font-weight:600;">Step {{ $step['num'] }}</label>
                    <label>Title
                        <input name="support_pages[returns][steps][{{ $si }}][title]" value="{{ $step['title'] }}" class="live-input live-input-save">
                    </label>
                    <label>Description
                        <textarea name="support_pages[returns][steps][{{ $si }}][desc]" rows="2" class="live-input live-input-save">{{ $step['desc'] }}</textarea>
                    </label>
                    <input type="hidden" name="support_pages[returns][steps][{{ $si }}][num]" value="{{ $step['num'] }}">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Careers: Page Body -->
        <div class="right-panel-content" id="panel-careers-body">
            <div class="panel-header">
                Page Body
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Page Body
                    <textarea name="company_pages[careers][body]" rows="6" class="live-input live-input-save">{{ old('company_pages.careers.body', $companyPagesData['careers']['body'] ?? '') }}</textarea>
                </label>
            </div>
        </div>

        <!-- Affiliates: Page Body -->
        <div class="right-panel-content" id="panel-affiliates-body">
            <div class="panel-header">
                Page Body & Benefits
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Page Body
                    <textarea name="company_pages[affiliates][body]" rows="4" class="live-input live-input-save">{{ old('company_pages.affiliates.body', $companyPagesData['affiliates']['body'] ?? '') }}</textarea>
                </label>
                <label style="margin-top: 16px;">CTA Label
                    <input name="company_pages[affiliates][cta_label]" value="{{ old('company_pages.affiliates.cta_label', $companyPagesData['affiliates']['cta_label'] ?? 'Apply Now') }}" class="live-input live-input-save">
                </label>
            </div>
        </div>

        <!-- Collections Page Custom Panels -->
        @php
            $targetSlug = in_array($context, ['collections', 'categories/category1', 'categories/category2', 'categories/category3', 'store/accessories', 'store/monitors', 'store/pc-parts']) ? $context : 'collections';
            $colPage = collect($customPages)->firstWhere('slug', $targetSlug) ?? [];
            $colPageIndex = array_search($colPage['id'] ?? $targetSlug, array_column($customPages, 'id'));
            if ($colPageIndex === false) {
                $colPageIndex = count($customPages);
            }
        @endphp
        <input type="hidden" name="custom_pages[{{ $colPageIndex }}][id]" value="{{ $colPage['id'] ?? $targetSlug }}">
        <input type="hidden" name="custom_pages[{{ $colPageIndex }}][slug]" value="{{ $colPage['slug'] ?? $targetSlug }}">
        <input type="hidden" name="custom_pages[{{ $colPageIndex }}][blueprint]" value="{{ $colPage['blueprint'] ?? 'collections' }}">

        <div class="right-panel-content" id="panel-collections-hero-main">
            <div class="panel-header">
                Banner
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Configure the Banner visibility of the Collections page.</p>
                <label style="display: flex; align-items: center; gap: 8px; margin-top: 16px;">
                    <input type="hidden" name="custom_pages[{{ $colPageIndex }}][show_hero]" value="0">
                    <input type="checkbox" name="custom_pages[{{ $colPageIndex }}][show_hero]" value="1" style="width:auto;" {{ old("custom_pages.{$colPageIndex}.show_hero", $colPage['show_hero'] ?? 1) ? 'checked' : '' }}>
                    <span style="font-size: 13px; color: #202223; font-weight: 500;">Show Banner</span>
                </label>
            </div>
        </div>

        <div class="right-panel-content" id="panel-collections-hero-title">
            <div class="panel-header">
                Hero Page Title
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => "custom_pages[{$colPageIndex}][title]", 'label' => 'Page Title', 'value' => old("custom_pages.{$colPageIndex}.title", $colPage['title'] ?? 'Collections')])
            </div>
        </div>

        <div class="right-panel-content" id="panel-collections-hero-subtitle">
            <div class="panel-header">
                Hero Subtitle
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                @include('ecommerce::components.admin.rich-text', ['name' => "custom_pages[{$colPageIndex}][subtitle]", 'label' => 'Subtitle / Description Text', 'value' => old("custom_pages.{$colPageIndex}.subtitle", $colPage['subtitle'] ?? 'Browse our full range of high quality products. Experience uncompromised quality, ready to ship directly to your door.')])
            </div>
        </div>


        <div class="right-panel-content" id="panel-collections-hero-bg">
            <div class="panel-header">
                Hero Background Image
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <label>Hero Background Image
                    <input type="file" name="custom_pages[{{ $colPageIndex }}][hero_image]" accept="image/*" style="margin-top: 8px;">
                </label>
                <p style="font-size: 12px; color: #6d7175; margin-top: 8px;">Upload a high resolution banner image for the category hero section.</p>
            </div>
        </div>

        @if($context === 'collections')
        @for ($idx = 0; $idx < 3; $idx++)
        @php
            $btnData = $colPage['category_buttons'][$idx] ?? [];
        @endphp
        <div class="right-panel-content" id="panel-collections-button-{{ $idx }}">
            <div class="panel-header">
                Button {{ $idx + 1 }}
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Configure this category quick-link.</p>
                <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;">
                    <label style="margin-top:0;">Label
                        <input name="custom_pages[{{ $colPageIndex }}][category_buttons][{{ $idx }}][label]" value="{{ old("custom_pages.{$colPageIndex}.category_buttons.{$idx}.label", $btnData['label'] ?? '') }}" placeholder="e.g. Laptops" class="live-input">
                    </label>
                    <label style="margin-top: 8px;">URL
                        <input name="custom_pages[{{ $colPageIndex }}][category_buttons][{{ $idx }}][url]" value="{{ old("custom_pages.{$colPageIndex}.category_buttons.{$idx}.url", $btnData['url'] ?? '') }}" placeholder="e.g. /collections/laptops" class="live-input">
                    </label>
                </div>
            </div>
        </div>
        @endfor
        @endif

        <div class="right-panel-content" id="panel-collections-grid-main">
            <div class="panel-header">
                Product Catalog
                <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="section-content">
                <p style="font-size: 13px; color: #5c5f62; margin-bottom: 16px;">Customize the featured item cards in the catalog. Add or select products to display prominently.</p>
            </div>
        </div>

        <div id="collections-blocks-panels">
            @php
                $colBlocks = $colPage['blocks'] ?? [];
                if (empty($colBlocks)) {
                    $firstListing = $availableListings->first();
                    $colBlocks = [['listing_id' => (string)($firstListing->id ?? '')]];
                }
            @endphp
            @foreach($colBlocks as $idx => $block)
            <div class="right-panel-content collections-block-panel" id="panel-collections-block-{{ $idx }}">
                <div class="panel-header">
                    <span>Item {{ $idx + 1 }}</span>
                    <button type="button" onclick="closeRightPanel()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="section-content">
                    <label>Select Listed Item
                        <select name="custom_pages[{{ $colPageIndex }}][blocks][{{ $idx }}][listing_id]" class="live-input" style="width: 100%; margin-top: 6px; padding: 8px; border: 1px solid #c9cccf; border-radius: 4px;">
                            <option value="">-- Default Listed Item --</option>
                            @foreach($availableListings as $listing)
                            <option value="{{ $listing->id }}" @selected((string)($block['listing_id'] ?? '') === (string)$listing->id)>
                                {{ $listing->name }} (&#8369;{{ number_format($listing->price, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </label>

                    <div style="margin-top: 24px; display: flex; gap: 8px;">
                        <button type="button" onclick="duplicateBlock('collections', {{ $idx }})" style="flex: 1; padding: 8px; border: 1px solid #c9cccf; background: #fff; color: #202223; border-radius: 4px; cursor: pointer;">Duplicate</button>
                        <button type="button" onclick="removeBlock('collections', {{ $idx }})" style="flex: 1; padding: 8px; border: 1px solid #ff4d4d; color: #ff4d4d; background: transparent; border-radius: 4px; cursor: pointer;">Remove</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</form>

<script>
    // Editor topbar user menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        var menus = document.querySelectorAll('[data-user-menu]');
        for (var i = 0; i < menus.length; i++) {
            (function(menu) {
                var btn = menu.querySelector('[data-user-menu-button]');
                if (!btn) return;
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var open = menu.getAttribute('data-open') !== 'true';
                    menu.setAttribute('data-open', open ? 'true' : 'false');
                    btn.setAttribute('aria-expanded', String(open));
                });
                document.addEventListener('click', function() {
                    menu.setAttribute('data-open', 'false');
                    btn.setAttribute('aria-expanded', 'false');
                });
            })(menus[i]);
        }
    });

    function toggleExpand(event, wrapperId) {
        event.stopPropagation();
        document.getElementById(wrapperId).classList.toggle('expanded');
    }

    function addBlock(section, duplicateFromIdx = null) {
        const container = document.getElementById(`${section}-blocks-container`);
        const panelsContainer = document.getElementById(`${section}-blocks-panels`);

        // Count existing blocks
        const blocks = container.querySelectorAll(`.${section}-block-nav`);
        const newIdx = blocks.length;

        // Create nav item
        const navItem = document.createElement('div');
        navItem.className = `sub-item ${section}-block-nav`;
        navItem.id = `nav-${section}-block-${newIdx}`;
        navItem.setAttribute('onclick', `openRightPanel('wrapper-${section}', 'panel-${section}-block-${newIdx}'); highlightSub(this);`);
        const itemName = 'Item';
        navItem.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg> <span>${itemName} ${newIdx + 1}</span>`;

        container.appendChild(navItem);

        // Clone an existing panel to use as template
        let templatePanel = panelsContainer.querySelector('.right-panel-content');
        if (!templatePanel) return; // Should at least have one by default

        const minCount = section === 'collections' ? 0 : 4;
        const namePrefix = section === 'collections' ? `custom_pages[{{ $collectionsPageIndex ?? 0 }}][blocks]` : `${section}_blocks`;

        const newPanel = templatePanel.cloneNode(true);
        newPanel.id = `panel-${section}-block-${newIdx}`;
        newPanel.classList.remove('active');

        // Update title and inputs inside new panel
        newPanel.querySelector('.panel-header span').innerText = `${itemName} ${newIdx + 1}`;

        const select = newPanel.querySelector('select');
        if (select) {
            select.name = `${namePrefix}[${newIdx}][listing_id]`;
            // If duplicating, set value to the original index value
            if (duplicateFromIdx !== null) {
                const originalSelect = document.querySelector(`select[name="${namePrefix}[${duplicateFromIdx}][listing_id]"]`);
                if (originalSelect) {
                    select.value = originalSelect.value;
                }
            } else {
                select.value = '';
            }

            // Re-bind live input event
            select.addEventListener('input', () => {
                if (typeof updateStaticPreview === 'function') updateStaticPreview();
            });
            select.addEventListener('change', () => {
                if (typeof updateStaticPreview === 'function') updateStaticPreview();
            });
        }

        const hiddenInput = newPanel.querySelector('input[type="hidden"][name*="[description]"]');
        const editorDiv = newPanel.querySelector('.rt-editor');

        if (hiddenInput && editorDiv) {
            const newId = 'rt_' + Math.random().toString(36).substring(2, 9);
            editorDiv.id = 'editor_' + newId;
            hiddenInput.id = 'hidden_' + newId;
            hiddenInput.name = `${namePrefix}[${newIdx}][description]`;

            if (duplicateFromIdx !== null) {
                const originalInput = document.querySelector(`input[name="${namePrefix}[${duplicateFromIdx}][description]"]`);
                if (originalInput) {
                    hiddenInput.value = originalInput.value;
                    editorDiv.innerHTML = originalInput.value;
                }
            } else {
                hiddenInput.value = '';
                editorDiv.innerHTML = '';
            }

            const updateFn = () => {
                hiddenInput.value = editorDiv.innerHTML;
                if (typeof window.updateStaticPreview === 'function') {
                    window.updateStaticPreview();
                }
            };

            editorDiv.oninput = updateFn;
            editorDiv.onblur = updateFn;

            newPanel.querySelectorAll('.rt-toolbar button').forEach(btn => {
                const title = btn.getAttribute('title');
                if (title === 'Bold') btn.onmousedown = (e) => { e.preventDefault(); document.execCommand('bold', false, null); updateFn(); };
                if (title === 'Italic') btn.onmousedown = (e) => { e.preventDefault(); document.execCommand('italic', false, null); updateFn(); };
                if (title === 'Link') btn.onmousedown = (e) => { e.preventDefault(); const url = prompt('Enter URL:'); if(url) { document.execCommand('createLink', false, url); updateFn(); } };
                if (title === 'Bullet List') btn.onmousedown = (e) => { e.preventDefault(); document.execCommand('insertUnorderedList', false, null); updateFn(); };
                if (title === 'Numbered List') btn.onmousedown = (e) => { e.preventDefault(); document.execCommand('insertOrderedList', false, null); updateFn(); };
            });
        }

        // Update buttons
        const duplicateBtn = newPanel.querySelector('button[onclick^="duplicateBlock"]');
        if (duplicateBtn) duplicateBtn.setAttribute('onclick', `duplicateBlock('${section}', ${newIdx})`);

        let removeBtn = newPanel.querySelector('button[onclick^="removeBlock"]');
        if (!removeBtn && newIdx >= minCount) {
            // Create remove button if duplicating from a block that doesn't have it
            removeBtn = document.createElement('button');
            removeBtn.setAttribute('type', 'button');
            removeBtn.setAttribute('style', 'flex:1; padding:8px; border:1px solid #ff4d4d; color:#ff4d4d; background:transparent; border-radius:4px; cursor:pointer;');
            removeBtn.innerText = 'Remove';
            newPanel.querySelector('div[style*="display:flex"]').appendChild(removeBtn);
        }
        if (removeBtn) {
            if (newIdx < minCount) {
                removeBtn.remove();
            } else {
                removeBtn.setAttribute('onclick', `removeBlock('${section}', ${newIdx})`);
            }
        }

        panelsContainer.appendChild(newPanel);

        // Open the newly created block
        navItem.click();
        if (typeof window.updateStaticPreview === 'function') window.updateStaticPreview();
    }

    function duplicateBlock(section, idx) {
        addBlock(section, idx);
    }

    function removeBlock(section, idx) {
        const minCount = section === 'collections' ? 0 : 4;
        const namePrefix = section === 'collections' ? `custom_pages[{{ $collectionsPageIndex ?? 0 }}][blocks]` : `${section}_blocks`;
        const container = document.getElementById(`${section}-blocks-container`);
        let blocks = container ? container.querySelectorAll(`.${section}-block-nav`) : [];

        if (blocks.length <= minCount) {
            showToast(`Minimum ${minCount} item card required.`, 'error');
            return;
        }

        const navItem = document.getElementById(`nav-${section}-block-${idx}`);
        const panelItem = document.getElementById(`panel-${section}-block-${idx}`);

        if (navItem) navItem.remove();
        if (panelItem) panelItem.remove();

        // Re-index remaining blocks
        const panelsContainer = document.getElementById(`${section}-blocks-panels`);

        blocks = container ? container.querySelectorAll(`.${section}-block-nav`) : [];
        const panels = panelsContainer ? panelsContainer.querySelectorAll('.right-panel-content') : [];

        blocks.forEach((block, index) => {
            block.id = `nav-${section}-block-${index}`;
            block.setAttribute('onclick', `openRightPanel('wrapper-${section}', 'panel-${section}-block-${index}'); highlightSub(this);`);
            const span = block.querySelector('span');
            if (span) span.innerText = `Item ${index + 1}`;
        });

        panels.forEach((panel, index) => {
            panel.id = `panel-${section}-block-${index}`;
            const headerSpan = panel.querySelector('.panel-header span');
            if (headerSpan) headerSpan.innerText = `Item ${index + 1}`;

            const select = panel.querySelector('select');
            if (select) {
                select.name = `${namePrefix}[${index}][listing_id]`;
            }

            const descInput = panel.querySelector('input[type="hidden"][name*="[description]"]') || panel.querySelector('textarea');
            if (descInput) {
                descInput.name = `${namePrefix}[${index}][description]`;
            }

            const duplicateBtn = panel.querySelector('button[onclick^="duplicateBlock"]');
            if (duplicateBtn) duplicateBtn.setAttribute('onclick', `duplicateBlock('${section}', ${index})`);

            const removeBtn = panel.querySelector('button[onclick^="removeBlock"]');
            if (removeBtn) {
                if (index < minCount) {
                    removeBtn.remove();
                } else {
                    removeBtn.setAttribute('onclick', `removeBlock('${section}', ${index})`);
                }
            }
        });

        closeRightPanel();
        if (typeof window.updateStaticPreview === 'function') window.updateStaticPreview();
    }

    function highlightSub(element) {
        document.querySelectorAll('.sub-item').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');
    }

    function openRightPanel(wrapperId, panelId) {
        // Expand the wrapper if not expanded
        const wrapper = document.getElementById(wrapperId);
        if (wrapper && !wrapper.classList.contains('expanded')) {
            wrapper.classList.add('expanded');
        }

        // Remove active sub-item highlights if clicking the parent
        // (the onclick of sub-item will override this if it was a sub-item click)
        if (typeof window.event !== 'undefined' && window.event && window.event.currentTarget && window.event.currentTarget.classList && window.event.currentTarget.classList.contains('nav-trigger')) {
            document.querySelectorAll('.sub-item').forEach(el => el.classList.remove('active'));
        }

        // Hide all right panel contents
        document.querySelectorAll('.right-panel-content').forEach(el => el.classList.remove('active'));

        // Show the target panel content
        const target = document.getElementById(panelId);
        if (target) {
            target.classList.add('active');
        }

        // Open the right sidebar
        document.getElementById('right-sidebar').classList.add('open');
        document.getElementById('preview-container').classList.add('panel-open');
        updateIframeScale();

        // Highlight the corresponding element in the preview iframe
        highlightPreview(wrapperId, panelId);
    }

    function closeRightPanel() {
        document.querySelectorAll('.sub-item').forEach(el => el.classList.remove('active'));
        document.getElementById('right-sidebar').classList.remove('open');
        document.getElementById('preview-container').classList.remove('panel-open');
        updateIframeScale();

        // Clear preview highlight when closing the panel
        try {
            const previewFrame = document.getElementById('preview-frame');
            const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            if (doc) {
                const prev = doc.querySelector('[data-preview-highlighted]');
                if (prev) {
                    prev.removeAttribute('data-preview-highlighted');
                    prev.style.outline = '';
                    prev.style.outlineOffset = '';
                    prev.style.boxShadow = '';
                    prev.style.borderRadius = '';
                }
            }
        } catch(e) {}
    }

    function updateIframeScale() {
        const preview = document.querySelector('.builder-preview');
        const inner = document.querySelector('.builder-preview-inner');
        const container = document.querySelector('.builder-container');

        // Calculate true width to avoid transition mid-state values
        const fullWidth = container.offsetWidth - 300 - 32; // Container minus left sidebar (300) minus horizontal padding (32)
        const targetHeight = container.offsetHeight - 32; // Container minus vertical padding (32)

        if (preview.classList.contains('panel-open')) {
            const newWidth = fullWidth - 320; // Minus right sidebar
            const scale = newWidth / fullWidth;

            inner.style.width = fullWidth + 'px';
            inner.style.height = (targetHeight / scale) + 'px';
            inner.style.transform = `scale(${scale})`;
            inner.style.flexGrow = '0';
            inner.style.flexShrink = '0';
        } else {
            inner.style.width = '100%';
            inner.style.height = '100%';
            inner.style.transform = 'scale(1)';
            inner.style.flexGrow = '1';
            inner.style.flexShrink = '1';
        }
    }

    /**
     * Highlight the corresponding section/block in the preview iframe
     * when a sidebar nav-item or sub-item is clicked.
     */
    function highlightPreview(wrapperId, panelId) {
        try {
            const previewFrame = document.getElementById('preview-frame');
            const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            if (!doc) return;

            // Clear previous highlight
            const prev = doc.querySelector('[data-preview-highlighted]');
            if (prev) {
                prev.removeAttribute('data-preview-highlighted');
                prev.style.outline = '';
                prev.style.outlineOffset = '';
                prev.style.boxShadow = '';
                prev.style.borderRadius = '';
            }

            // Determine which section element to highlight.
            // Support/company pages use the full wrapperId as data-preview-section (e.g. "wrapper-support-heading-contact").
            // Template sections use the stripped ID (e.g. "wrapper-hero" -> "hero").
            let sectionEl = doc.querySelector('[data-preview-section="' + wrapperId + '"]');
            let sectionId = wrapperId;
            if (!sectionEl) {
                sectionId = wrapperId.replace('wrapper-', '');
                sectionEl = doc.querySelector('[data-preview-section="' + sectionId + '"]');
            }
            if (!sectionEl) return;

            // Determine if clicking the section header (main panel) or a sub-item (block)
            const isSectionMain = panelId === 'panel-' + sectionId + '-main';

            if (isSectionMain) {
                // Highlight the entire section
                sectionEl.setAttribute('data-preview-highlighted', 'true');
                sectionEl.style.outline = '3px solid #0060df';
                sectionEl.style.outlineOffset = '2px';
                sectionEl.style.boxShadow = '0 0 0 4px rgba(0, 96, 223, 0.25)';
                sectionEl.style.borderRadius = '4px';
                sectionEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // Try to find a specific block with this panel ID
                const blockEl = doc.querySelector('[data-preview-block="' + panelId + '"]');
                if (blockEl) {
                    blockEl.setAttribute('data-preview-highlighted', 'true');
                    blockEl.style.outline = '3px solid #0060df';
                    blockEl.style.outlineOffset = '2px';
                    blockEl.style.boxShadow = '0 0 0 4px rgba(0, 96, 223, 0.25)';
                    blockEl.style.borderRadius = '4px';
                    blockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Fallback: highlight the entire section
                    sectionEl.setAttribute('data-preview-highlighted', 'true');
                    sectionEl.style.outline = '3px solid #0060df';
                    sectionEl.style.outlineOffset = '2px';
                    sectionEl.style.boxShadow = '0 0 0 4px rgba(0, 96, 223, 0.25)';
                    sectionEl.style.borderRadius = '4px';
                    sectionEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        } catch(e) {
            // Silently ignore cross-origin or DOM errors
        }
    }

    window.addEventListener('resize', () => {
        // debounce resize
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(updateIframeScale, 100);
    });

    (() => {
        const form = document.getElementById('layout-form');
        const iframe = document.getElementById('preview-frame');

        let debounceTimer;

        const availableListingsMap = @json($availableListings->keyBy('id'));

        // Static UI updating
        const updateStaticPreview = () => {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                if (!doc) return;

                const formData = new FormData(form);

                const syncSectionCards = (sectionId, containerSelector, itemPrefix, blockPanelClass, minCards = 0) => {
                    const sectionEl = doc.querySelector(`[data-preview-section="${sectionId}"]`);
                    if (!sectionEl) return;

                    let carousel = sectionEl.querySelector(containerSelector);
                    if (!carousel) return;

                    const blockPanels = document.querySelectorAll(`.${blockPanelClass}`);
                    let cards = sectionEl.querySelectorAll(`[data-preview-block^="${itemPrefix}"]`);

                    // Add missing cards in preview iframe if new blocks were added
                    while (cards.length < blockPanels.length) {
                        const lastCard = cards[cards.length - 1];
                        if (!lastCard) break;
                        const newCard = lastCard.cloneNode(true);
                        const newIdx = cards.length;
                        newCard.setAttribute('data-preview-block', `${itemPrefix}${newIdx}`);
                        newCard.removeAttribute('data-preview-bound');

                        const numBadge = newCard.querySelector('.font-mono');
                        if (numBadge) numBadge.textContent = `/0${newIdx + 1}`;

                        carousel.appendChild(newCard);
                        cards = sectionEl.querySelectorAll(`[data-preview-block^="${itemPrefix}"]`);
                    }

                    // Remove excess cards in preview iframe if blocks were deleted
                    while (cards.length > blockPanels.length && cards.length > minCards) {
                        const lastCard = cards[cards.length - 1];
                        if (lastCard) lastCard.remove();
                        cards = sectionEl.querySelectorAll(`[data-preview-block^="${itemPrefix}"]`);
                    }

                    // Now update each card's content based on the form data
                    cards.forEach((card, idx) => {
                        let listingId = formData.get(`${sectionId}_blocks[${idx}][listing_id]`);
                        if (listingId === null) {
                            listingId = formData.get(`custom_pages[{{ $colPageIndex ?? 0 }}][blocks][${idx}][listing_id]`);
                        }
                        let descVal = formData.get(`${sectionId}_blocks[${idx}][description]`);
                        if (descVal === null) {
                            descVal = formData.get(`custom_pages[{{ $colPageIndex ?? 0 }}][blocks][${idx}][description]`);
                        }
                        const listing = listingId ? availableListingsMap[listingId] : null;

                        const titleEl = card.querySelector('h3');
                        const priceEl = card.querySelector('.text-primary.text-2xl');
                        const imgEl = card.querySelector('img');
                        const descEl = card.querySelector('p.text-gray-300');

                        if (listing) {
                            if (titleEl) titleEl.textContent = listing.name;
                            if (priceEl) priceEl.innerHTML = '&#8369;' + Number(listing.price).toLocaleString();
                            if (imgEl) {
                                imgEl.src = listing.image_url ? `/storage/${listing.image_url}` : 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                                imgEl.alt = listing.name;
                            }
                            if (descEl) {
                                const defaultDesc = listing.description || 'No description available for this item.';
                                const cleanOverride = descVal ? descVal.replace(/<br\s*\/?>/gi, '').trim() : '';
                                descEl.innerHTML = cleanOverride !== '' ? descVal : defaultDesc;
                            }
                        } else {
                            if (titleEl) titleEl.textContent = `Select an Item (${idx + 1})`;
                            if (priceEl) priceEl.innerHTML = '&#8369;0';
                            if (descEl) {
                                const cleanOverride = descVal ? descVal.replace(/<br\s*\/?>/gi, '').trim() : '';
                                descEl.innerHTML = cleanOverride !== '' ? descVal : 'Select an item in the layout editor.';
                            }
                        }
                    });
                };

                // Sync Section Visibility (based on enabled checkbox)
                const sectionIds = ['hero', 'tiers', 'prebuilts', 'categories', 'cta'];
                sectionIds.forEach(id => {
                    const chk = formData.get(`${id}_enabled`);
                    const isEnabled = (formData.has(`${id}_enabled`) || chk === 'on' || chk === '1');
                    const secEl = doc.querySelector(`[data-preview-section="${id}"]`);
                    if (secEl) {
                        secEl.style.display = isEnabled ? '' : 'none';
                    }
                });

                // Sync Section Order
                const sectionOrderVal = formData.get('section_order');
                if (sectionOrderVal) {
                    const orderArray = sectionOrderVal.split(',').map(s => s.trim()).filter(Boolean);
                    const container = doc.querySelector('.pt-\\[140px\\]') || doc.body;
                    if (container) {
                        orderArray.forEach(id => {
                            const secEl = doc.querySelector(`[data-preview-section="${id}"]`);
                            if (secEl) {
                                container.appendChild(secEl);
                            }
                        });
                    }
                }

                // Hero
                const heroH1 = doc.querySelector('main[data-preview-section="hero"] h1');
                if (heroH1) {
                    let title = formData.get('hero_title') || '';
                    heroH1.innerHTML = title.replace(/\{(.*?)\}/g, '<span class="text-primary drop-shadow-glow">$1</span>');

                    const color = formData.get('hero_title_color');
                    if (color) {
                        heroH1.style.color = color;
                        heroH1.classList.remove('text-white');
                    }

                    const titleWidth = formData.get('hero_title_width') || 'auto';
                    heroH1.classList.remove('w-full', 'max-w-2xl', 'w-auto');
                    if (titleWidth === 'full') heroH1.classList.add('w-full');
                    else if (titleWidth === 'narrow') heroH1.classList.add('max-w-2xl');
                    else heroH1.classList.add('w-auto');

                    const titlePreset = formData.get('hero_title_preset') || 'h1';
                    heroH1.classList.remove(
                        'text-4xl', 'sm:text-5xl', 'font-bold', 'uppercase', 'leading-tight', 'tracking-wide',
                        'text-3xl', 'sm:text-4xl', 'font-semibold', 'leading-snug',
                        'text-lg', 'sm:text-xl', 'font-normal', 'leading-relaxed',
                        'text-5xl', 'sm:text-6xl', 'lg:text-7xl', 'font-black', 'leading-[1.1]', 'tracking-wider'
                    );
                    if (titlePreset === 'h2') {
                        heroH1.classList.add('text-4xl', 'sm:text-5xl', 'font-bold', 'uppercase', 'leading-tight', 'tracking-wide');
                    } else if (titlePreset === 'h3') {
                        heroH1.classList.add('text-3xl', 'sm:text-4xl', 'font-semibold', 'uppercase', 'leading-snug');
                    } else if (titlePreset === 'body') {
                        heroH1.classList.add('text-lg', 'sm:text-xl', 'font-normal', 'leading-relaxed');
                    } else {
                        heroH1.classList.add('text-5xl', 'sm:text-6xl', 'lg:text-7xl', 'font-black', 'uppercase', 'leading-[1.1]', 'tracking-wider');
                    }
                }

                const heroP = doc.querySelector('main[data-preview-section="hero"] p.text-gray-400');
                if (heroP) {
                    heroP.innerHTML = formData.get('hero_body') || '';
                }

                // Collections Hero
                const colTitle = doc.querySelector('.collections-hero-title');
                if (colTitle) {
                    const tVal = formData.get('custom_pages[{{ $colPageIndex }}][title]');
                    if (tVal !== null) colTitle.innerHTML = tVal;
                }
                const colSubtitle = doc.querySelector('.collections-hero-subtitle');
                if (colSubtitle) {
                    const sVal = formData.get('custom_pages[{{ $colPageIndex }}][subtitle]');
                    if (sVal !== null) colSubtitle.innerHTML = sVal;
                }

                // Button Label
                const heroBtnLabel = doc.querySelector('main[data-preview-section="hero"] a.bg-primary');
                if (heroBtnLabel) {
                    const btnLabel = formData.get('hero_buttons[0][label]');
                    if (btnLabel !== null) {
                        heroBtnLabel.innerHTML = btnLabel + ' &rarr;';
                    }
                }

                // CTA Subtext
                const heroCtaSubtext = doc.querySelector('main[data-preview-section="hero"] p.text-gray-500.text-xs.font-semibold');
                if (heroCtaSubtext) {
                    const subtext = formData.get('hero_cta_subtext');
                    if (subtext !== null) {
                        heroCtaSubtext.textContent = subtext;
                    }
                }

                // Stats
                const statsBlock = doc.querySelector('[data-preview-block="panel-hero-stats"]');
                if (statsBlock) {
                    for (let i = 0; i < 3; i++) {
                        const statVal = formData.get(`hero_stats[${i}][value]`);
                        const statLab = formData.get(`hero_stats[${i}][label]`);
                        if (statsBlock.children[i]) {
                            const valEl = statsBlock.children[i].querySelector('.text-xl, .text-2xl');
                            const labEl = statsBlock.children[i].querySelector('.text-gray-500');
                            if (valEl && statVal !== null) valEl.innerHTML = statVal;
                            if (labEl && statLab !== null) labEl.textContent = statLab;
                        }
                    }
                }

                // Overlay Opacity
                const overlay = doc.querySelector('[data-preview-block="panel-hero-image"] .absolute.inset-0.bg-black');
                if (overlay) {
                    const opacity = formData.get('hero_overlay_opacity') || 0;
                    overlay.style.opacity = opacity / 100;
                }

                // Features Marquee
                const marqueeBlock = doc.querySelector('[data-preview-block="panel-hero-marquee"]');
                if (marqueeBlock) {
                    const marqueeSpans = marqueeBlock.querySelectorAll('.animate-marquee span');
                    const items = [];
                    for (let i = 0; i < 6; i++) {
                        const val = formData.get(`hero_marquee[${i}][text]`);
                        if (val && val.trim()) items.push(val.trim());
                    }
                    if (items.length > 0 && marqueeSpans.length > 0) {
                        let idx = 0;
                        marqueeSpans.forEach(span => {
                            span.textContent = items[idx % items.length];
                            idx++;
                        });
                    }
                }

                // Tiers
                const tiersH2 = doc.querySelector('section[data-preview-section="tiers"] h2');
                if (tiersH2) {
                    const title = formData.get('tiers_title') || '';
                    tiersH2.innerHTML = title;
                }
                const tiersP = doc.querySelector('section[data-preview-section="tiers"] p.text-gray-400');
                if (tiersP) {
                    tiersP.textContent = formData.get('tiers_body') || '';
                }
                syncSectionCards('tiers', '#tiers-carousel', 'panel-tiers-block-', 'tiers-block-panel', 4);

                // Prebuilts
                const prebuiltsH2 = doc.querySelector('section[data-preview-section="prebuilts"] h2');
                if (prebuiltsH2) {
                    const title = formData.get('prebuilts_title') || '';
                    prebuiltsH2.innerHTML = title;
                }
                const prebuiltsP = doc.querySelector('section[data-preview-section="prebuilts"] p.text-gray-400');
                if (prebuiltsP) {
                    prebuiltsP.textContent = formData.get('prebuilts_body') || '';
                }
                syncSectionCards('prebuilts', '#prebuilt-carousel', 'panel-prebuilts-block-', 'prebuilts-block-panel', 4);

                // Categories
                const categoriesH2 = doc.querySelector('section[data-preview-section="categories"] h2');
                if (categoriesH2) {
                    const title = formData.get('categories_title') || '';
                    categoriesH2.innerHTML = title.replace(/\n/g, '<br>');
                }
                const categoriesP = doc.querySelector('section[data-preview-section="categories"] p.text-gray-400');
                if (categoriesP) {
                    categoriesP.textContent = formData.get('categories_body') || '';
                }
                // Category cards live preview
                for (let ci = 0; ci < 3; ci++) {
                    const card = doc.querySelector(`[data-cat-card="${ci}"]`);
                    if (!card) continue;
                    const titleInput = formData.get(`categories[blocks][${ci}][title]`);
                    if (titleInput !== null) {
                        const titleEl = card.querySelector('.cat-card-title');
                        if (titleEl) titleEl.textContent = titleInput;
                    }
                    const descInput = formData.get(`categories[blocks][${ci}][description]`);
                    if (descInput !== null) {
                        const descEl = card.querySelector('.cat-card-desc');
                        if (descEl) descEl.textContent = descInput;
                    }

                    const imgInput = formData.get(`categories[blocks][${ci}][image]`);
                    if (imgInput !== null) {
                        const imgEl = card.querySelector('.cat-card-img');
                        if (imgEl) imgEl.src = imgInput;
                    }
                }

                // Collections Custom Page Live Reflection
                const colTitleEl = doc.querySelector('.collections-hero-title') || doc.querySelector('[data-preview-block="panel-collections-hero-title"]');
                if (colTitleEl) {
                    const colIdx = {{ $collectionsPageIndex ?? 0 }};
                    const colTitleVal = formData.get(`custom_pages[${colIdx}][title]`);
                    if (colTitleVal !== null && colTitleVal !== '') {
                        colTitleEl.innerHTML = colTitleVal;
                    }
                }

                const colSubtitleEl = doc.querySelector('.collections-hero-subtitle') || doc.querySelector('[data-preview-block="panel-collections-hero-subtitle"]');
                if (colSubtitleEl) {
                    const colIdx = {{ $collectionsPageIndex ?? 0 }};
                    const colSubVal = formData.get(`custom_pages[${colIdx}][subtitle]`);
                    if (colSubVal !== null && colSubVal !== '') {
                        colSubtitleEl.innerHTML = colSubVal;
                    }
                }

                // Sync Collection/Category hero background image from file input preview
                const heroBgImg = doc.querySelector('[data-preview-block="panel-collections-hero-bg"] img');
                if (heroBgImg) {
                    // Find the file input that has a data-preview-url
                    const heroFileInput = document.querySelector('input[type="file"][name^="custom_pages"][name$="[hero_image]"]');
                    if (heroFileInput && heroFileInput.getAttribute('data-preview-url')) {
                        heroBgImg.src = heroFileInput.getAttribute('data-preview-url');
                    }
                }

                // Sync Collection Buttons
                const colButtonsUl = doc.getElementById('collections-buttons-list');
                if (colButtonsUl) {
                    let btnHtml = '';
                    let hasAnyBtn = false;
                    for (let i = 0; i < 3; i++) {
                        const lbl = formData.get(`custom_pages[{{ $colPageIndex ?? 0 }}][category_buttons][${i}][label]`);
                        const url = formData.get(`custom_pages[{{ $colPageIndex ?? 0 }}][category_buttons][${i}][url]`);
                        if (lbl) {
                            btnHtml += `<li><a href="${url || '#'}" data-parent-section="collections-buttons" data-preview-block="panel-collections-button-${i}" class="px-8 py-3 rounded-full bg-white/5 border border-white/10 text-white hover:bg-white hover:text-black hover:border-white transition-all font-semibold shadow-lg inline-block">${lbl}</a></li>`;
                            hasAnyBtn = true;
                        }
                    }
                    if (!hasAnyBtn) {
                        btnHtml = `<li><a href="#" data-parent-section="collections-buttons" data-preview-block="panel-collections-button-0" class="px-8 py-3 rounded-full bg-white/5 border border-white/10 text-white hover:bg-white hover:text-black hover:border-white transition-all font-semibold shadow-lg opacity-50 inline-block">Category 1</a></li>` +
                                  `<li><a href="#" data-parent-section="collections-buttons" data-preview-block="panel-collections-button-1" class="px-8 py-3 rounded-full bg-white/5 border border-white/10 text-white hover:bg-white hover:text-black hover:border-white transition-all font-semibold shadow-lg opacity-50 inline-block">Category 2</a></li>` +
                                  `<li><a href="#" data-parent-section="collections-buttons" data-preview-block="panel-collections-button-2" class="px-8 py-3 rounded-full bg-white/5 border border-white/10 text-white hover:bg-white hover:text-black hover:border-white transition-all font-semibold shadow-lg opacity-50 inline-block">Category 3</a></li>`;
                    }
                    colButtonsUl.innerHTML = btnHtml;
                    initPreviewBinds(); // Rebind events for dynamically created buttons
                }

                syncSectionCards('collections-grid', '#product-grid', 'panel-collections-block-', 'collections-block-panel');

                // CTA
                const ctaTag = doc.querySelector('#cta-tag');
                if (ctaTag) {
                    ctaTag.textContent = formData.get('cta_tag_text') || '';
                }
                const ctaTitle = doc.querySelector('section[data-preview-section="cta"] h2 span.text-white');
                if (ctaTitle) {
                    ctaTitle.textContent = formData.get('cta_title') || '';
                }
                const ctaSubtitle = doc.querySelector('section[data-preview-section="cta"] h2 span.text-primary');
                if (ctaSubtitle) {
                    ctaSubtitle.textContent = formData.get('cta_subtitle') || '';
                }
                const ctaP = doc.querySelector('section[data-preview-section="cta"] p.text-gray-400');
                if (ctaP) {
                    ctaP.textContent = formData.get('cta_body') || '';
                }
                const ctaBtnPrimary = doc.querySelector('section[data-preview-section="cta"] a.bg-primary');
                if (ctaBtnPrimary) {
                    const btnLabel = formData.get('cta_primary_button_label');
                    if (btnLabel !== null) ctaBtnPrimary.innerHTML = btnLabel + ' &rarr;';
                }
                const ctaBtnSecondary = doc.querySelector('section[data-preview-section="cta"] a.border-white\\/20');
                if (ctaBtnSecondary) {
                    const btnLabel = formData.get('cta_secondary_button_label');
                    if (btnLabel !== null) {
                        ctaBtnSecondary.textContent = btnLabel;
                        iframe.contentWindow.postMessage({ type: 'staticSync' }, '*');
                    }
                }
                // Brand Name & Logo Sync
                const brandNameSpan = doc.querySelector('nav span.text-white');
                if (brandNameSpan) {
                    const bName = formData.get('brand_name');
                    if (bName !== null && bName !== '') brandNameSpan.textContent = bName;
                }

                // Sync logo in preview
                const logoImages = doc.querySelectorAll('img[alt*="logo"]');
                if (logoImages.length > 0) {
                    const logoUrl = window._editorLogoDataUrl || '';
                    if (logoUrl) {
                        logoImages.forEach(function(img) { img.src = logoUrl; });
                    }
                }

                // Announcement Bar Sync
                const annBar = doc.getElementById('announcement-bar');
                const mainNav = doc.getElementById('main-nav') || doc.querySelector('nav');
                const secNav = doc.getElementById('secondary-nav');

                const annEnabled = formData.get('announcement_enabled') !== null;
                const annText = formData.get('announcement_text');
                const annUrl = formData.get('announcement_url');

                if (annBar) {
                    if (annEnabled) {
                        annBar.classList.remove('hidden');
                        annBar.style.display = 'flex';
                        if (mainNav) {
                            mainNav.classList.remove('top-4');
                            mainNav.classList.add('top-10');
                        }
                        if (secNav) {
                            secNav.classList.remove('top-[112px]');
                            secNav.classList.add('top-[136px]');
                        }
                    } else {
                        annBar.classList.add('hidden');
                        annBar.style.display = 'none';
                        if (mainNav) {
                            mainNav.classList.remove('top-10');
                            mainNav.classList.add('top-4');
                        }
                        if (secNav) {
                            secNav.classList.remove('top-[136px]');
                            secNav.classList.add('top-[112px]');
                        }
                    }

                    const textEl = doc.getElementById('announcement-text-el');
                    if (textEl && annText !== null) {
                        textEl.textContent = annText;
                    }

                    const linkEl = doc.getElementById('announcement-link');
                    const arrowEl = doc.getElementById('announcement-arrow');
                    if (linkEl && annUrl !== null) {
                        linkEl.href = annUrl || '#';
                        if (annUrl) {
                            linkEl.classList.remove('pointer-events-none');
                            if (arrowEl) arrowEl.classList.remove('hidden');
                        } else {
                            linkEl.classList.add('pointer-events-none');
                            if (arrowEl) arrowEl.classList.add('hidden');
                        }
                    }
                }

                // Secondary Nav Links Live Sync
                for (let i = 0; i < 3; i++) {
                    const linkEl = doc.getElementById('sec-nav-link-' + i);
                    if (linkEl) {
                        const label = formData.get('navbar[links][' + i + '][label]');
                        if (label !== null) {
                            linkEl.textContent = label.trim() === '' ? 'LINK' : label;
                            linkEl.style.display = '';
                        }
                        const url = formData.get('navbar[links][' + i + '][url]');
                        if (url !== null) {
                            linkEl.href = url || '#';
                        }
                    }
                }

                // Footer Live Sync
                const footerDesc = doc.getElementById('footer-description-el');
                if (footerDesc) {
                    const desc = formData.get('footer_description');
                    if (desc !== null) footerDesc.textContent = desc;
                }

                const col1H4 = doc.getElementById('footer-col1-title-el');
                if (col1H4) {
                    const col1 = formData.get('footer_column_1_title');
                    if (col1 !== null) col1H4.textContent = col1;
                }

                const col2H4 = doc.getElementById('footer-col2-title-el');
                if (col2H4) {
                    const col2 = formData.get('footer_column_2_title');
                    if (col2 !== null) col2H4.textContent = col2;
                }

                const col3H4 = doc.getElementById('footer-col3-title-el');
                if (col3H4) {
                    const col3 = formData.get('footer_column_3_title');
                    if (col3 !== null) col3H4.textContent = col3;
                }

                const footerCopy = doc.getElementById('footer-copyright-el');
                if (footerCopy) {
                    const copyText = formData.get('footer_copyright_text');
                    const bName = formData.get('brand_name') || 'Store';
                    const yr = new Date().getFullYear();
                    if (copyText !== null) {
                        footerCopy.innerHTML = `&copy; ${yr} ${bName}. ${copyText || 'All rights reserved.'}`;
                    }
                }

                ['instagram', 'twitter', 'facebook', 'youtube'].forEach(platform => {
                    const socialLink = doc.getElementById(`footer-social-${platform}`);
                    if (socialLink) {
                        const url = formData.get(`footer_social_${platform}`);
                        if (url !== null) socialLink.href = url || '#';
                    }
                });

                // Sync Shop Links (URLs are hardcoded, only labels are editable)
                const shopUl = doc.getElementById('footer-shop-links-list');
                if (shopUl) {
                    const shopUrls = ['/collections', '/categories/category1', '/categories/category2', '/categories/category3'];
                    let shopHtml = '';
                    for (let i = 0; i < 4; i++) {
                        const lbl = formData.get(`footer_shop_links[${i}][label]`);
                        if (lbl) {
                            shopHtml += `<li><a href="${shopUrls[i]}" class="hover:text-white transition-colors">${lbl}</a></li>`;
                        }
                    }
                    if (shopHtml) shopUl.innerHTML = shopHtml;
                }

                // Company links are now hardcoded in the footer component (About Us, Careers, Affiliates)

                // Dynamic Theme Color Sync
                const primaryColor = formData.get('primary_color');
                const accentColor = formData.get('accent_color');

                function hexToRgbStr(hex, fallback) {
                    if (!hex) hex = fallback;
                    hex = hex.replace('#', '');
                    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                    const num = parseInt(hex, 16);
                    if (isNaN(num)) return '255, 107, 0';
                    return `${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}`;
                }

                if (primaryColor || accentColor) {
                    let themeStyleTag = doc.getElementById('dynamic-theme-vars');
                    if (!themeStyleTag) {
                        themeStyleTag = doc.createElement('style');
                        themeStyleTag.id = 'dynamic-theme-vars';
                        doc.head.appendChild(themeStyleTag);
                    }
                    const pColor = primaryColor || '#ff6b00';
                    const aColor = accentColor || '#f59e0b';
                    const pRgb = hexToRgbStr(pColor, '#ff6b00');
                    const aRgb = hexToRgbStr(aColor, '#f59e0b');

                    themeStyleTag.innerHTML = `
                        :root {
                            --theme-primary: ${pColor} !important;
                            --theme-primary-rgb: ${pRgb} !important;
                            --theme-accent: ${aColor} !important;
                            --theme-accent-rgb: ${aRgb} !important;
                        }
                        .text-primary { color: ${pColor} !important; }
                        .bg-primary { background-color: ${pColor} !important; }
                        .border-primary { border-color: ${pColor} !important; }
                        .text-accent { color: ${aColor} !important; }
                        .bg-accent { background-color: ${aColor} !important; }
                        .border-accent { border-color: ${aColor} !important; }
                        .shadow-glow { box-shadow: 0 0 20px rgba(${pRgb}, 0.5) !important; }
                        .shadow-glow-lg { box-shadow: 0 0 30px rgba(${pRgb}, 0.35) !important; }
                        .shadow-glow-sm { box-shadow: 0 0 10px rgba(${pRgb}, 0.4) !important; }
                        .drop-shadow-glow { filter: drop-shadow(0 0 15px rgba(${pRgb}, 0.5)) !important; }
                        .drop-shadow-glow-lg { filter: drop-shadow(0 0 25px rgba(${pRgb}, 0.6)) !important; }
                        .drop-shadow-glow-sm { filter: drop-shadow(0 0 10px rgba(${pRgb}, 0.5)) !important; }
                        .from-primary { --tw-gradient-from: ${pColor} !important; --tw-gradient-to: rgb(255 255 255 / 0) !important; --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-from), var(--tw-gradient-to)) !important; }
                        .to-primary { --tw-gradient-to: ${pColor} !important; }
                        .to-accent { --tw-gradient-to: ${aColor} !important; }
                        .ambient-light-1 { background: radial-gradient(circle, rgba(${pRgb}, 0.35) 0%, transparent 65%) !important; }
                        .ambient-light-2 { background: radial-gradient(circle, rgba(${aRgb}, 0.4) 0%, transparent 65%) !important; }
                    `;
                }

                // Update right panel preview bar if exists
                const previewBar = document.getElementById('color-preview-bar');
                if (previewBar && primaryColor && accentColor) {
                    previewBar.style.background = `linear-gradient(135deg, ${primaryColor}, ${accentColor})`;
                }

                // Support Pages & Company Pages Live Sync — find elements by data-sp-field and update their text
                for (const [key, value] of formData.entries()) {
                    let prefix = null;
                    if (key.startsWith('support_pages[')) prefix = 'support_pages[';
                    else if (key.startsWith('company_pages[')) prefix = 'company_pages[';
                    
                    if (prefix && value && typeof value === 'string') {
                        const fieldId = key.replace(new RegExp('^' + prefix.replace('[', '\\[')), '').replace(/\]$/, '').replace(/\]\[/g, '-');
                        try {
                            const target = doc.querySelector('[data-sp-field="' + fieldId + '"]');
                            if (target && target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA' && target.tagName !== 'SELECT') {
                                target.textContent = value;
                            }
                        } catch (e) {}
                    }
                }
            } catch (e) {
                console.warn('Could not sync static preview', e);
            }
        };
        window.updateStaticPreview = updateStaticPreview;

        window.applyColorPreset = function(primaryHex, accentHex) {
            const pPicker = document.getElementById('primary-color-picker');
            const pHex = document.getElementById('primary-color-hex');
            const aPicker = document.getElementById('accent-color-picker');
            const aHex = document.getElementById('accent-color-hex');
            const previewBar = document.getElementById('color-preview-bar');

            if (pPicker) pPicker.value = primaryHex;
            if (pHex) pHex.value = primaryHex;
            if (aPicker) aPicker.value = accentHex;
            if (aHex) aHex.value = accentHex;
            if (previewBar) previewBar.style.background = `linear-gradient(135deg, ${primaryHex}, ${accentHex})`;

            if (typeof window.updateStaticPreview === 'function') {
                window.updateStaticPreview();
            }
        };

        window.syncColorInput = function(pickerId, hexValue) {
            const picker = document.getElementById(pickerId);
            const pVal = document.getElementById('primary-color-picker')?.value;
            const aVal = document.getElementById('accent-color-picker')?.value;
            const previewBar = document.getElementById('color-preview-bar');

            if (picker && /^#([0-9A-F]{3}){1,2}$/i.test(hexValue)) {
                picker.value = hexValue;
                if (previewBar) {
                    const pHex = pickerId === 'primary-color-picker' ? hexValue : pVal;
                    const aHex = pickerId === 'accent-color-picker' ? hexValue : aVal;
                    previewBar.style.background = `linear-gradient(135deg, ${pHex}, ${aHex})`;
                }
                if (typeof window.updateStaticPreview === 'function') {
                    window.updateStaticPreview();
                }
            }
        };

        // Sync pickers with hex text inputs
        const pPicker = document.getElementById('primary-color-picker');
        const pHex = document.getElementById('primary-color-hex');
        const aPicker = document.getElementById('accent-color-picker');
        const aHex = document.getElementById('accent-color-hex');

        if (pPicker) {
            pPicker.addEventListener('input', (e) => {
                if (pHex) pHex.value = e.target.value;
            });
        }
        if (aPicker) {
            aPicker.addEventListener('input', (e) => {
                if (aHex) aHex.value = e.target.value;
            });
        }

        // Live Preview Logic
        const refreshPreview = async () => {
            const formData = new FormData(form);
            try {
                const response = await fetch(form.action, {
                    method: 'PUT',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const url = new URL(iframe.src);
                    url.searchParams.set('t', Date.now());
                    iframe.src = url.toString();

                    showToast('Draft saved successfully!');
                } else {
                    const err = await response.json();
                    console.error("Save failed:", err);
                    showToast('Failed to save draft.', 'error');
                }
            } catch (err) {
                console.error("Live preview sync failed", err);
                showToast('Network error while saving.', 'error');
            }
        };
        window.refreshPreview = refreshPreview;

        // Auto-save on page refresh/unload
        window.skipAutoSave = false;
        window.addEventListener('beforeunload', (e) => {
            if (!window.skipAutoSave) {
                const formData = new FormData(form);
                navigator.sendBeacon(form.action, formData);
            }
        });

        // Sync multiple inputs with the same name across right panels (brand_name, announcement_*, footer_*)
        ['brand_name', 'announcement_enabled', 'announcement_text', 'announcement_url', 'footer_description', 'footer_copyright_text', 'footer_column_1_title', 'footer_column_2_title', 'footer_column_3_title'].forEach(fieldName => {
            document.querySelectorAll(`input[name="${fieldName}"], textarea[name="${fieldName}"]`).forEach(input => {
                const evtType = input.type === 'checkbox' ? 'change' : 'input';
                input.addEventListener(evtType, (e) => {
                    const val = input.type === 'checkbox' ? e.target.checked : e.target.value;
                    document.querySelectorAll(`input[name="${fieldName}"], textarea[name="${fieldName}"]`).forEach(other => {
                        if (other !== e.target) {
                            if (input.type === 'checkbox') other.checked = val;
                            else other.value = val;
                        }
                    });
                    if (typeof window.updateStaticPreview === 'function') {
                        window.updateStaticPreview();
                    }
                });
            });
        });

        // Attach listeners to all inputs for immediate reflection
        document.querySelectorAll('.live-input').forEach(input => {
            input.addEventListener('input', () => {
                updateStaticPreview(); // Immediate reflection
            });
            if (input.type === 'checkbox') {
                input.addEventListener('change', () => {
                    updateStaticPreview();
                });
            }
        });

        // Collections/Category hero background image — use FileReader to show a temporary preview
        document.querySelectorAll('input[type="file"][name^="custom_pages"][name$="[hero_image]"]').forEach(function(field) {
            field.addEventListener('change', function(e) {
                var file = e.target.files && e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    var input = this;
                    reader.onload = function(ev) {
                        input.setAttribute('data-preview-url', ev.target.result);
                        updateStaticPreview();
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.removeAttribute('data-preview-url');
                    updateStaticPreview();
                }
            });
        });

        // Logo file input — use FileReader to show a temporary preview
        var logoFileInputs = document.querySelectorAll('input[type="file"][name="logo"]');
        for (var li = 0; li < logoFileInputs.length; li++) {
            logoFileInputs[li].addEventListener('change', function(e) {
                var file = e.target.files && e.target.files[0];
                var container = this.closest('.section-content');
                if (file) {
                    var reader = new FileReader();
                    var self = this;
                    reader.onload = function(ev) {
                        var dataUrl = ev.target.result;
                        window._editorLogoDataUrl = dataUrl;
                        // Update right-panel logo preview card
                        if (container) {
                            var existingCard = container.querySelector('.logo-preview-dynamic, .logo-preview-card');
                            if (existingCard) {
                                // Update existing card — swap logo image src
                                var cardImg = existingCard.querySelector('.logo-preview-img');
                                if (cardImg && cardImg.tagName === 'IMG') {
                                    cardImg.src = dataUrl;
                                } else if (cardImg) {
                                    // Replace text placeholder with img
                                    var row = existingCard.querySelector('.logo-preview-row');
                                    if (row) {
                                        var newImg = document.createElement('img');
                                        newImg.className = 'logo-preview-img';
                                        newImg.src = dataUrl;
                                        newImg.alt = 'Logo preview';
                                        newImg.style.cssText = 'max-height:36px;object-fit:contain;';
                                        row.insertBefore(newImg, cardImg);
                                        cardImg.parentNode.removeChild(cardImg);
                                    }
                                }
                            } else {
                                // No preview existed — create full branded card
                                var brandInput = container.querySelector('input[name="brand_name"]');
                                var storeName = brandInput ? brandInput.value : 'Your Store Name';

                                var card = document.createElement('div');
                                card.className = 'logo-preview-card logo-preview-dynamic';
                                card.style.cssText = 'margin-top:12px;border:1px solid #e1e3e5;border-radius:8px;overflow:hidden;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.06);';

                                var navInner = document.createElement('div');
                                navInner.className = 'logo-preview-inner';
                                navInner.style.cssText = 'background:#0f172a;padding:20px;display:flex;align-items:center;justify-content:center;min-height:72px;';

                                var logoRow = document.createElement('div');
                                logoRow.className = 'logo-preview-row';
                                logoRow.style.cssText = 'display:flex;align-items:center;gap:12px;';

                                var img = document.createElement('img');
                                img.className = 'logo-preview-img';
                                img.src = dataUrl;
                                img.alt = 'Logo preview';
                                img.style.cssText = 'max-height:36px;object-fit:contain;';
                                logoRow.appendChild(img);

                                var nameSpan = document.createElement('span');
                                nameSpan.className = 'logo-preview-name';
                                nameSpan.style.cssText = 'color:#fff;font-size:15px;font-weight:700;letter-spacing:-0.3px;';
                                nameSpan.textContent = storeName;
                                logoRow.appendChild(nameSpan);

                                navInner.appendChild(logoRow);
                                card.appendChild(navInner);

                                var footerDiv = document.createElement('div');
                                footerDiv.style.cssText = 'padding:8px 12px;background:#f8f9fb;border-top:1px solid #e1e3e5;font-size:11px;color:#6d7175;display:flex;align-items:center;gap:6px;';
                                footerDiv.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Navbar preview — logo + store name';
                                card.appendChild(footerDiv);

                                // Insert after the file input's parent label element
                                var parentLabel = self.closest('label');
                                if (parentLabel && parentLabel.nextSibling) {
                                    container.insertBefore(card, parentLabel.nextSibling);
                                } else {
                                    container.appendChild(card);
                                }
                            }
                        }
                        updateStaticPreview();
                    };
                    reader.readAsDataURL(file);
                } else {
                    delete window._editorLogoDataUrl;
                    // Remove only dynamically-created previews (not the original stored one)
                    if (container) {
                        var dynamicPreview = container.querySelector('.logo-preview-dynamic');
                        if (dynamicPreview) dynamicPreview.parentNode.removeChild(dynamicPreview);
                    }
                    updateStaticPreview();
                }
            });
        }

        // Sync brand_name changes into the logo preview card name
        document.querySelectorAll('input[name="brand_name"]').forEach(function(bnInput) {
            bnInput.addEventListener('input', function() {
                var val = this.value || 'Your Store Name';
                document.querySelectorAll('.logo-preview-card .logo-preview-name').forEach(function(span) {
                    span.textContent = val;
                });
                // Also update the placeholder text in any card ("[STORE] LOGO" format)
                document.querySelectorAll('.logo-preview-card .logo-preview-placeholder').forEach(function(el) {
                    var storeName = (val || 'STORE').trim().toUpperCase();
                    el.textContent = storeName + ' LOGO';
                });
                updateStaticPreview();
            });
        });

        // Remove Logo checkbox — clear file input and reset preview when checked
        document.querySelectorAll('input[name="remove_logo"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    var container = this.closest('.section-content');
                    if (container) {
                        var fileInput = container.querySelector('input[type="file"][name="logo"]');
                        if (fileInput) fileInput.value = '';
                    }
                    delete window._editorLogoDataUrl;
                    updateStaticPreview();
                }
            });
        });



        // Drag and drop ordering for sections
        const sortableList = document.getElementById('sortable-sections');
        if (sortableList) {
            let draggedItem = null;

            sortableList.addEventListener('dragstart', (e) => {
                draggedItem = e.target.closest('.nav-item-wrapper');
                if (draggedItem) {
                    draggedItem.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                }
            });

            sortableList.addEventListener('dragend', (e) => {
                if (draggedItem) {
                    draggedItem.style.opacity = '1';
                    draggedItem = null;
                }
            });

            sortableList.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = getDragAfterElement(sortableList, e.clientY);
                const wrapper = e.target.closest('.nav-item-wrapper');
                if (draggedItem && wrapper && wrapper !== draggedItem) {
                    if (afterElement == null) {
                        sortableList.appendChild(draggedItem);
                    } else {
                        sortableList.insertBefore(draggedItem, afterElement);
                    }
                    updateSectionOrder();
                }
            });

            function getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll('.nav-item-wrapper:not([style*="opacity: 0.5"])')];

                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    } else {
                        return closest;
                    }
                }, { offset: Number.NEGATIVE_INFINITY }).element;
            }

            function updateSectionOrder() {
                const wrappers = sortableList.querySelectorAll('.nav-item-wrapper');
                const newOrder = Array.from(wrappers).map(w => w.getAttribute('data-section-id')).filter(Boolean).join(',');
                const orderInput = document.getElementById('section-order');
                if (orderInput.value !== newOrder) {
                    orderInput.value = newOrder;
                    if (typeof window.updateStaticPreview === 'function') {
                        window.updateStaticPreview();
                    }
                }
            }
        }

        if (iframe) {
            iframe.addEventListener('load', () => {
                if (typeof window.updateStaticPreview === 'function') {
                    window.updateStaticPreview();
                }
            });
        }

        // Toast Notification System
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; pointer-events: none;';
        document.body.appendChild(toastContainer);

        window.showToast = function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                padding: 12px 24px;
                border-radius: 6px;
                color: #fff;
                font-weight: 500;
                font-size: 14px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                background: ${type === 'success' ? '#008060' : '#d8000c'};
            `;
            toast.innerText = message;

            toastContainer.appendChild(toast);

            // Trigger reflow
            void toast.offsetWidth;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        // Publish Live Modal Handlers
        window.openPublishModal = function() {
            const modal = document.getElementById('publish-modal');
            if (modal) {
                modal.style.display = 'flex';
                void modal.offsetWidth;
                modal.classList.add('open');
            }
        };

        window.closePublishModal = function() {
            const modal = document.getElementById('publish-modal');
            if (modal) {
                modal.classList.remove('open');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            }
        };

        // Save Draft — shows loading overlay, then submits the form normally
        // (regular form submit handles file uploads more reliably than AJAX FormData)
        window.openSaveDraftModal = function() {
            const overlay = document.getElementById('save-loading-overlay');
            if (overlay) {
                overlay.style.display = 'flex';
            }
            const form = document.getElementById('layout-form');
            if (form) {
                // Use a tiny delay so the loading overlay renders before the page navigates
                setTimeout(function() { form.submit(); }, 80);
            }
        };

        window.closeSaveDraftModal = function() {
            const modal = document.getElementById('save-draft-modal');
            if (modal) {
                modal.classList.remove('open');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            }
        };

        window.submitPublishLive = async function() {
            // Show the save-loading overlay during publish
            const pubOverlay = document.getElementById('save-loading-overlay');
            if (pubOverlay) {
                pubOverlay.querySelector('.loading-text').textContent = 'Publishing your storefront';
                pubOverlay.querySelector('.loading-sub').textContent = 'Making your changes live...';
                pubOverlay.style.display = 'flex';
            }

            closePublishModal();

            const btn = document.getElementById('confirm-publish-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Publishing...';
            }

            window.skipAutoSave = true;
            const form = document.getElementById('layout-form');
            const formData = new FormData(form);
            formData.delete('_method');

            try {
                const response = await fetch("{{ route('ecommerce.admin.layout.publish') }}?context={{ $context }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    showToast('Storefront published live successfully!');
                    const iframe = document.getElementById('preview-frame');
                    if (iframe) {
                        const url = new URL(iframe.src);
                        url.searchParams.set('t', Date.now());
                        iframe.src = url.toString();
                    }
                } else {
                    const err = await response.json();
                    console.error("Publish failed:", err);
                    const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(' ') : 'Failed to publish live.');
                    showToast(msg, 'error');
                }
            } catch (err) {
                console.error("Publish request failed", err);
                showToast('Network error while publishing.', 'error');
            } finally {
                // Always hide the overlay and reset text
                if (pubOverlay) {
                    pubOverlay.style.display = 'none';
                    pubOverlay.querySelector('.loading-text').textContent = 'Saving your changes';
                    pubOverlay.querySelector('.loading-sub').textContent = 'Please wait while we save your draft...';
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Yes, Publish Live';
                }
            }
        };

        // Custom Page Dropdown Toggle
        window.togglePageDropdown = function(e) {
            e.stopPropagation();
            const container = document.getElementById('customPageDropdown');
            if (container) {
                container.classList.toggle('open');
            }
        };

        // Show page-loading overlay when clicking a page dropdown item
        document.querySelectorAll('.page-dropdown-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                // Only intercept if it's navigating to a different context
                var href = this.getAttribute('href');
                if (href && href.indexOf('context=') !== -1) {
                    // Show overlay for any page selection (including the current one for refresh)
                    var overlay = document.getElementById('page-loading-overlay');
                    if (overlay) {
                        overlay.style.display = 'flex';
                    }
                    // Close the dropdown before navigation
                    var dd = document.getElementById('customPageDropdown');
                    if (dd) dd.classList.remove('open');
                }
            });
        });

        // Hide page-loading overlay once the iframe finishes loading
        var pageLoadIframe = document.getElementById('preview-frame');
        if (pageLoadIframe) {
            pageLoadIframe.addEventListener('load', function() {
                var overlay = document.getElementById('page-loading-overlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            });
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('customPageDropdown');
            if (container && !container.contains(e.target)) {
                container.classList.remove('open');
            }
        });

        // Show flash messages on page load if any
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif

        @if($errors->any())
            showToast("{{ $errors->first() }}", 'error');
        @endif
        const iframeEl = document.getElementById('preview-frame');
        if (iframeEl) {
            iframeEl.addEventListener('load', function() {
                try {
                    const iframeDoc = iframeEl.contentDocument || iframeEl.contentWindow.document;
                    iframeDoc.addEventListener('click', function(e) {
                        const link = e.target.closest('a');
                        if (link) {
                            e.preventDefault();
                        }
                    });
                } catch(err) {
                    console.error('Could not prevent link clicks', err);
                }
            });
        }

    })();
</script>

<!-- Publish Confirmation Modal -->
<div id="publish-modal" class="publish-modal-backdrop" style="display: none;">
    <div class="publish-modal-card">
        <div class="publish-modal-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <h3 class="publish-modal-title">Publish Storefront Live?</h3>
        <p class="publish-modal-desc">This will make your latest storefront design and layout changes visible to all live site visitors. Are you sure you want to publish now?</p>
        <div class="publish-modal-actions">
            <button type="button" class="publish-btn-cancel" onclick="closePublishModal()">Cancel</button>
            <button type="button" class="publish-btn-confirm" id="confirm-publish-btn" onclick="submitPublishLive()">Yes, Publish Live</button>
        </div>
    </div>
</div>

<!-- Save Draft Success Modal -->
<div id="save-draft-modal" class="publish-modal-backdrop" style="display: none;">
    <div class="publish-modal-card">
        <div class="publish-modal-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1B6FC8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
        </div>
        <h3 class="publish-modal-title">Draft Saved</h3>
        <p class="publish-modal-desc">Your storefront layout changes have been saved as a draft. You can continue editing or publish live when ready.</p>
        <div class="publish-modal-actions" style="justify-content: center;">
            <button type="button" class="publish-btn-confirm" onclick="closeSaveDraftModal()">Got it</button>
        </div>
    </div>
</div>

<!-- Page Loading Overlay (shown when switching pages) -->
<div id="page-loading-overlay" class="page-loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text">Loading page</div>
    <div class="loading-sub">Preparing your editor...</div>
</div>

<!-- Save Draft Loading Overlay -->
<div id="save-loading-overlay" class="save-loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text">Saving your changes</div>
    <div class="loading-sub">Please wait while we save your draft...</div>
</div>

@endsection
