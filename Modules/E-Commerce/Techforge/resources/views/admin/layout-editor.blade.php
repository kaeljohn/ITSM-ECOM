@extends('ecommerce::admin.layout', ['title' => 'Edit Storefront', 'heading' => 'Edit Storefront'])

@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';

    $sections = collect($layout['sections'] ?? [])->keyBy('id');
    $hero = $sections->get('hero', []);
    $listings = $sections->get('featured_listings', []);
    $promo = $sections->get('promo', []);
    $benefits = $sections->get('benefits', []);
    $order = implode(',', array_column($layout['sections'] ?? [], 'id'));
    
    $navbar = $layout['navbar'] ?? [];
    $links = $navbar['links'] ?? [];


@endphp

@section('content')
<style>
    /* Full height override */
    body { overflow: hidden; }
    .page { width: 100% !important; max-width: 100% !important; padding: 0 !important; display: flex; flex-direction: column; height: calc(100vh - 128px); }
    .page-heading { display: none; }
    .success { margin: 16px; display: none; } /* Hide default success */

    .builder-container { display: flex; height: 100%; overflow: hidden; }
    
    /* Sidebar Styling */
    .builder-sidebar { width: 420px; min-width: 420px; background: #F4F6FA; color: #0B1E3D; overflow-y: auto; border-right: 1px solid #E2E8F0; display: flex; flex-direction: column; }
    .builder-sidebar::-webkit-scrollbar { width: 8px; }
    .builder-sidebar::-webkit-scrollbar-track { background: #F4F6FA; }
    .builder-sidebar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }
    .builder-sidebar::-webkit-scrollbar-thumb:hover { background: #7BBEF0; }

    .builder-sidebar .card { background: transparent; border: none; box-shadow: none; color: #0B1E3D; padding: 24px; margin: 0; }
    .builder-sidebar label { color: #5B7A9D; margin-top: 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .builder-sidebar input, .builder-sidebar textarea, .builder-sidebar select { background: #FFFFFF; border: 1px solid #E2E8F0; color: #0B1E3D; border-radius: 8px; padding: 12px; margin-top: 8px; outline: none; transition: all 0.2s; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02); width: 100%; box-sizing: border-box; }
    .builder-sidebar input:focus, .builder-sidebar textarea:focus, .builder-sidebar select:focus { border-color: #7BBEF0; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02), 0 0 0 3px rgba(123, 190, 240, 0.15); }
    .builder-sidebar select { appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%235B7A9D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px; padding-right: 36px; cursor: pointer; }
    
    .builder-sidebar details.section-card { background: #FFFFFF; border: 1px solid #E2E8F0; margin-top: 24px; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); transition: all 0.2s; }
    .builder-sidebar details.section-card > summary { list-style: none; cursor: pointer; user-select: none; display: flex; align-items: center; justify-content: space-between; margin: -20px; padding: 20px; outline: none; }
    .builder-sidebar details.section-card > summary::-webkit-details-marker { display: none; }
    .builder-sidebar details.section-card > summary h3 { color: #0B1E3D; font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; display: flex; align-items: center; }
    .builder-sidebar details.section-card > summary h3::before { content: '▶'; font-size: 11px; color: #5B7A9D; display: inline-block; transition: transform 0.2s; margin-right: 8px; }
    .builder-sidebar details[open].section-card > summary h3::before { transform: rotate(90deg); }
    .builder-sidebar details[open].section-card > summary { margin-bottom: 20px; padding-bottom: 0; }
    .builder-sidebar .toggle { color: #5B7A9D; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
    .builder-sidebar .toggle input { appearance: none; -webkit-appearance: none; width: 36px; height: 20px; background: #CBD5E1; border-radius: 20px; position: relative; cursor: pointer; outline: none; transition: background 0.3s; margin: 0; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
    .builder-sidebar .toggle input::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #FFFFFF; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.2); transition: transform 0.3s; }
    .builder-sidebar .toggle input:checked { background: #1B6FC8; }
    .builder-sidebar .toggle input:checked::after { transform: translateX(16px); }
    .builder-sidebar h2 { color: #0B1E3D; font-size: 20px; font-weight: 900; margin-bottom: 8px; }
    .builder-sidebar .muted { color: #5B7A9D; font-size: 13px; line-height: 1.5; margin-bottom: 24px; }
    
    .builder-sidebar .btn-save { background: #1B6FC8; color: #FFFFFF; width: 100%; padding: 14px; margin-top: 24px; border-radius: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(27,111,200,0.2); }
    .builder-sidebar .btn-save:hover { background: #4A9EE8; color: #FFFFFF; box-shadow: 0 6px 16px rgba(74,158,232,0.3); transform: translateY(-1px); }
    .builder-sidebar .btn-publish { background: #FFFFFF; color: #0B1E3D; width: 100%; padding: 14px; margin-top: 16px; border-radius: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s; border: 1px solid #E2E8F0; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .builder-sidebar .btn-publish:hover { background: #F4F6FA; border-color: #1B6FC8; color: #1B6FC8; }
    
    .builder-sidebar .order-list { margin-top: 20px; gap: 10px; }
    .builder-sidebar .order-item { background: #FFFFFF; border: 1px solid #E2E8F0; color: #0B1E3D; border-radius: 8px; padding: 12px 16px; font-size: 13px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; }
    .builder-sidebar .order-item button { background: #F4F6FA; color: #5B7A9D; border: 1px solid #E2E8F0; border-radius: 4px; padding: 6px 10px; cursor: pointer; transition: all 0.2s; }
    .builder-sidebar .order-item button:hover { background: #1B6FC8; color: #FFFFFF; border-color: #1B6FC8; }
    
    .builder-sidebar .publish-note { background: #D6ECFC; border-left: 4px solid #1B6FC8; color: #0B1E3D; padding: 16px; margin-top: 24px; border-radius: 0 8px 8px 0; }
    .builder-sidebar .publish-note code { color: #1B6FC8; font-weight: 700; background: rgba(27,111,200,0.1); padding: 2px 4px; border-radius: 4px; }
    
    #add-nav-link-btn { background: #FFFFFF !important; color: #1B6FC8 !important; padding: 12px !important; border: 2px dashed #7BBEF0 !important; border-radius: 8px; width: 100%; cursor: pointer; text-transform: uppercase; font-size: 11px; font-weight: 800; margin-top: 12px; transition: all 0.2s; }
    #add-nav-link-btn:hover { background: #F4F6FA !important; border-color: #1B6FC8 !important; color: #1B6FC8 !important; }

    /* Iframe Styling */
    .builder-preview { flex-grow: 1; background: #FFFFFF; position: relative; display: flex; flex-direction: column; }
    .builder-preview iframe { width: 100%; flex-grow: 1; border: none; }
    
    .preview-header { background: #FFFFFF; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); z-index: 10; relative; }
    .preview-header .title { color: #0B1E3D; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
    .preview-header .status { font-size: 12px; font-weight: 600; color: #5B7A9D; display: flex; align-items: center; gap: 8px; }
    
    .save-indicator { opacity: 0; transform: translateY(-10px); transition: all 0.3s; background: #16A34A; color: #FFFFFF; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(22,163,74,0.3); }
    .save-indicator.show { opacity: 1; transform: translateY(0); }
    
    .sortable-ghost { opacity: 0.4; background: #F8FAFC !important; border: 2px dashed #7BBEF0 !important; }
</style>

<div class="builder-container">
    <div class="builder-sidebar">
        <form id="layout-form" class="card" method="post" enctype="multipart/form-data" action="{{ route('ecommerce.admin.layout.save') }}">
            @csrf @method('put')
            
            <h2>Storefront Content</h2>
            <p class="muted">Configure the blocks of your storefront. Changes made here will update the preview instantly when saved.</p>
            
            <div class="field-grid">
                <label>Store name<input name="brand_name" value="{{ old('brand_name', $layout['brand_name']) }}" required></label>
                <label>Tagline<input name="tagline" value="{{ old('tagline', $layout['tagline']) }}"></label>
                <label style="display:flex; flex-direction:column; gap:8px;">Primary color
                    <div style="display:flex; gap:12px; align-items:center;">
                        <input type="color" name="primary_color" value="{{ old('primary_color', $layout['primary_color']) }}" style="height:36px; padding:2px; width:48px;" required>
                        <span style="font-family:monospace; color:#5B7A9D">{{ old('primary_color', $layout['primary_color']) }}</span>
                    </div>
                </label>
                <label style="display:flex; flex-direction:column; gap:8px;">Accent color
                    <div style="display:flex; gap:12px; align-items:center;">
                        <input type="color" name="accent_color" value="{{ old('accent_color', $layout['accent_color']) }}" style="height:36px; padding:2px; width:48px;" required>
                        <span style="font-family:monospace; color:#5B7A9D">{{ old('accent_color', $layout['accent_color']) }}</span>
                    </div>
                </label>
            </div>
            
            <label>Store logo (Optional)
                <input type="file" name="logo" accept="image/*" style="padding-top:10px;">
            </label>

            <input id="section-order" type="hidden" name="section_order" value="{{ old('section_order', $order) }}">

            <details class="section-card" data-section="navbar" open>
                <summary class="section-top"><h3>Navbar & Header</h3></summary>
                
                <h4 style="margin-top:20px; font-size:12px; text-transform:uppercase; color:#1B6FC8;">Search Experience</h4>
                <div class="field-grid">
                    <label>Placeholder Text<input name="search_placeholder" value="{{ old('search_placeholder', $navbar['search_placeholder'] ?? '') }}" placeholder="What are we searching?"></label>
                    <label>Trending Searches<input name="trending_searches" value="{{ old('trending_searches', $navbar['trending_searches'] ?? '') }}" placeholder="Comma separated list"></label>
                </div>
                
                <h4 style="margin-top:20px; font-size:12px; text-transform:uppercase; color:#1B6FC8;">Announcement Bar</h4>
                <label class="toggle" style="margin-bottom:10px;"><input type="checkbox" name="announcement_enabled" @checked(old('announcement_enabled', $navbar['announcement_enabled'] ?? false))> Enable Announcement Bar</label>
                <div class="field-grid">
                    <label>Message<input name="announcement_text" value="{{ old('announcement_text', $navbar['announcement_text'] ?? '') }}"></label>
                    <label>Link URL (Optional)<input name="announcement_url" value="{{ old('announcement_url', $navbar['announcement_url'] ?? '') }}"></label>
                </div>

                                <h4 style="margin-top:20px; font-size:12px; text-transform:uppercase; color:#1B6FC8;">Navigation Links</h4>
                <p class="muted" style="margin-bottom:12px;">Add up to 10 custom links or mega-menus.</p>
                <div id="nav-links-container" style="display: flex; flex-direction: column; gap: 12px;"></div>
                <button type="button" id="add-nav-link-btn" style="background: #FFFFFF; color: #1B6FC8; padding: 10px; border: 2px dashed #7BBEF0; border-radius: 8px; width: 100%; cursor: pointer; text-transform: uppercase; font-size: 11px; font-weight: bold; margin-top: 10px; transition: all 0.2s;">
                    + Add Link
                </button>
            </details>


            <div id="sortable-sections" style="display: flex; flex-direction: column;">
                <details class="section-card" data-section="hero">
                    <summary class="section-top"><h3 style="cursor: grab;"><svg class="drag-handle" style="width:16px; height:16px; margin-right:8px; color:#94A3B8; flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg> Hero</h3></summary>
                    <label class="toggle" style="margin-top: 0; margin-bottom: 16px;"><input type="checkbox" name="hero_enabled" @checked(old('hero_enabled', $hero['enabled'] ?? false))> Visible</label>
                    <label>Headline (Wrap text in {brackets} to highlight)<input name="hero_title" value="{{ old('hero_title', $hero['title'] ?? '') }}"></label>
                    <label>Description<textarea name="hero_body" rows="3">{{ old('hero_body', $hero['body'] ?? '') }}</textarea></label>
                    
                    <details style="margin-top:20px; border:1px solid #E2E8F0; border-radius:8px; padding:12px; background:#F8FAFC; margin-bottom:12px;">
                        <summary style="font-size:12px; font-weight:bold; text-transform:uppercase; color:#1B6FC8; cursor:pointer;">Layout & Effects</summary>
                        <div style="margin-top:16px;">
                            <div class="field-grid">
                                <label>Image Dimming Overlay (%)
                                    <input type="number" name="hero_overlay_opacity" min="0" max="100" value="{{ old('hero_overlay_opacity', $hero['overlay_opacity'] ?? 0) }}">
                                </label>
                            </div>
                            <label class="toggle" style="margin-top: 8px; margin-bottom: 12px;"><input type="checkbox" name="hero_particles_enabled" @checked(old('hero_particles_enabled', $hero['particles_enabled'] ?? false))> Enable Ambient Particles</label>
                            <div class="field-grid">
                                <label>Particle Count
                                    <input type="number" name="hero_particles_count" min="10" max="200" value="{{ old('hero_particles_count', $hero['particles_count'] ?? 40) }}">
                                </label>
                                <label>Particle Speed (1x = normal)
                                    <input type="number" step="0.1" name="hero_particles_speed" min="0.1" max="10" value="{{ old('hero_particles_speed', $hero['particles_speed'] ?? 1.0) }}">
                                </label>
                            </div>
                        </div>
                    </details>

                    <details style="border:1px solid #E2E8F0; border-radius:8px; padding:12px; background:#F8FAFC; margin-bottom:12px;">
                        <summary style="font-size:12px; font-weight:bold; text-transform:uppercase; color:#1B6FC8; cursor:pointer;">Gallery Configuration</summary>
                        <div style="margin-top:16px;">
                            <div class="field-grid">
                                <label>Visual Style
                                    <select name="hero_visual_style" onchange="document.getElementById('hero-badge-wrap').style.opacity = this.value === 'gallery' ? '1' : '0.5'; document.getElementById('hero-cycle-wrap').style.opacity = this.value === 'gallery' ? '1' : '0.5'; document.getElementById('hero-gallery-configs').style.display = this.value === 'gallery' ? 'block' : 'none';">
                                        <option value="showcase" @selected(old('hero_visual_style', $hero['visual_style'] ?? 'showcase') === 'showcase')>Showcase (Single Image)</option>
                                        <option value="gallery" @selected(old('hero_visual_style', $hero['visual_style'] ?? 'showcase') === 'gallery')>Featured Gallery (Thumbnails)</option>
                                    </select>
                                </label>
                                <label id="hero-badge-wrap" style="transition: opacity 0.2s; opacity: {{ (old('hero_visual_style', $hero['visual_style'] ?? 'showcase') === 'gallery') ? '1' : '0.5' }}">Badge Text
                                    <input name="hero_badge_text" value="{{ old('hero_badge_text', $hero['badge_text'] ?? 'FEATURED BUILD') }}">
                                </label>
                                <label id="hero-cycle-wrap" style="transition: opacity 0.2s; opacity: {{ (old('hero_visual_style', $hero['visual_style'] ?? 'showcase') === 'gallery') ? '1' : '0.5' }}">Cycle Interval (sec)
                                    <input type="number" name="hero_gallery_cycle" min="1" max="60" value="{{ old('hero_gallery_cycle', $hero['gallery_cycle'] ?? 5) }}">
                                </label>
                            </div>

                            <div id="hero-gallery-configs" style="display: {{ (old('hero_visual_style', $hero['visual_style'] ?? 'showcase') === 'gallery') ? 'block' : 'none' }}; margin-top: 16px;">
                                <label style="margin-bottom:8px; display:block; font-size: 11px; font-weight: bold; color: #64748B;">Select 4 Featured Builds</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    @for($i = 0; $i < 4; $i++)
                                    <select name="hero_featured_configs[]">
                                        <option value="">-- Select Build (Slot {{ $i + 1 }}) --</option>
                                        @foreach($availableConfigs as $config)
                                            <option value="{{ $config->id }}" @selected(($hero['featured_configs'][$i] ?? null) == $config->id)>{{ $config->name }} - {{ $config->tier }}</option>
                                        @endforeach
                                    </select>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </details>

                    <details style="border:1px solid #E2E8F0; border-radius:8px; padding:12px; background:#F8FAFC; margin-bottom:12px;">
                        <summary style="font-size:12px; font-weight:bold; text-transform:uppercase; color:#1B6FC8; cursor:pointer;">Buttons</summary>
                        <div style="margin-top:16px;">
                            <label>Alignment
                                <select name="hero_button_alignment">
                                    <option value="start" @selected(old('hero_button_alignment', $hero['button_alignment'] ?? 'start') === 'start')>Left</option>
                                    <option value="center" @selected(old('hero_button_alignment', $hero['button_alignment'] ?? 'start') === 'center')>Center</option>
                                    <option value="end" @selected(old('hero_button_alignment', $hero['button_alignment'] ?? 'start') === 'end')>Right</option>
                                </select>
                            </label>
                            <div id="hero-buttons-container" style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;"></div>
                            <button type="button" id="add-hero-button-btn" style="background: #FFFFFF; color: #1B6FC8; padding: 10px; border: 2px dashed #7BBEF0; border-radius: 8px; width: 100%; cursor: pointer; text-transform: uppercase; font-size: 11px; font-weight: bold; margin-top: 10px; transition: all 0.2s;">
                                + Add Button
                            </button>
                            
                            <label style="margin-top:16px;">CTA Subtext (Optional message under buttons)
                                <input name="hero_cta_subtext" value="{{ old('hero_cta_subtext', $hero['cta_subtext'] ?? '') }}">
                            </label>
                        </div>
                    </details>
                </details>

                <details class="section-card" data-section="featured_listings">
                    <summary class="section-top"><h3 style="cursor: grab;"><svg class="drag-handle" style="width:16px; height:16px; margin-right:8px; color:#94A3B8; flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg> Featured products</h3></summary>
                    <label class="toggle" style="margin-top: 0; margin-bottom: 16px;"><input type="checkbox" name="featured_listings_enabled" @checked(old('featured_listings_enabled', $listings['enabled'] ?? false))> Visible</label>
                    <div class="field-grid"><label>Section title<input name="listings_title" value="{{ old('listings_title', $listings['title'] ?? '') }}"></label><label>Supporting text<input name="listings_body" value="{{ old('listings_body', $listings['body'] ?? '') }}"></label></div>
                </details>

                <details class="section-card" data-section="promo">
                    <summary class="section-top"><h3 style="cursor: grab;"><svg class="drag-handle" style="width:16px; height:16px; margin-right:8px; color:#94A3B8; flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg> Promotional banner</h3></summary>
                    <label class="toggle" style="margin-top: 0; margin-bottom: 16px;"><input type="checkbox" name="promo_enabled" @checked(old('promo_enabled', $promo['enabled'] ?? false))> Visible</label>
                    <label>Headline<input name="promo_title" value="{{ old('promo_title', $promo['title'] ?? '') }}"></label>
                    <label>Message<textarea name="promo_body" rows="2">{{ old('promo_body', $promo['body'] ?? '') }}</textarea></label>
                    <h4 style="margin-top:20px; font-size:12px; text-transform:uppercase; color:#1B6FC8;">Buttons</h4>
                    <label>Alignment
                        <select name="promo_button_alignment">
                            <option value="start" @selected(old('promo_button_alignment', $promo['button_alignment'] ?? 'center') === 'start')>Left</option>
                            <option value="center" @selected(old('promo_button_alignment', $promo['button_alignment'] ?? 'center') === 'center')>Center</option>
                            <option value="end" @selected(old('promo_button_alignment', $promo['button_alignment'] ?? 'center') === 'end')>Right</option>
                        </select>
                    </label>
                    <div id="promo-buttons-container" style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;"></div>
                    <button type="button" id="add-promo-button-btn" style="background: #FFFFFF; color: #1B6FC8; padding: 10px; border: 2px dashed #7BBEF0; border-radius: 8px; width: 100%; cursor: pointer; text-transform: uppercase; font-size: 11px; font-weight: bold; margin-top: 10px; transition: all 0.2s;">
                        + Add Button
                    </button>
                </details>

                <details class="section-card" data-section="benefits">
                    <summary class="section-top"><h3 style="cursor: grab;"><svg class="drag-handle" style="width:16px; height:16px; margin-right:8px; color:#94A3B8; flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg> Benefits</h3></summary>
                    <label class="toggle" style="margin-top: 0; margin-bottom: 16px;"><input type="checkbox" name="benefits_enabled" @checked(old('benefits_enabled', $benefits['enabled'] ?? false))> Visible</label>
                    <label>Section title<input name="benefits_title" value="{{ old('benefits_title', $benefits['title'] ?? '') }}"></label>
                    <div class="field-grid"><label>Benefit 1<input name="benefit_one" value="{{ old('benefit_one', $benefits['benefit_one'] ?? '') }}"></label><label>Benefit 2<input name="benefit_two" value="{{ old('benefit_two', $benefits['benefit_two'] ?? '') }}"></label></div>
                    <label>Benefit 3<input name="benefit_three" value="{{ old('benefit_three', $benefits['benefit_three'] ?? '') }}"></label>
                </details>
            </div>
            
            <div style="position: sticky; bottom: -24px; margin: 24px -24px -24px -24px; padding: 16px 24px; background: rgba(244, 246, 250, 0.95); backdrop-filter: blur(4px); border-top: 1px solid #E2E8F0; z-index: 10;">
                <button type="submit" class="btn-save" id="save-btn" style="margin-top: 0;">Save Draft</button>
            </div>
        </form>

        <div class="card" style="padding-top: 0; padding-bottom: 48px;">

            <div class="publish-note">
                <strong>{{ $hasPublishedLayout ? 'A live layout already exists.' : 'Your current TechForge-style storefront remains live.' }}</strong><br><br>
                Publishing replaces the public homepage for<br><code>{{ $company->ecommerce_slug }}.{{ config('ecommerce.storefront_base_domain') }}</code>
            </div>
            
            <form method="post" action="{{ route('ecommerce.admin.layout.publish') }}">
                @csrf
                <button type="submit" class="btn-publish">Publish Live</button>
            </form>
        </div>
    </div>

    <div class="builder-preview">
        <div class="preview-header">
            <span class="title">Live Preview <a href="{{ route('ecommerce.admin.layout.preview') }}" target="_blank" style="margin-left: 12px; font-size: 10px; color: #1B6FC8; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; text-transform: none; letter-spacing: normal;">Open in new tab <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg></a></span>
            <div class="status">
                <span class="save-indicator" id="save-indicator">Saved Successfully</span>
                <span id="sync-status">Syncing with draft...</span>
            </div>
        </div>
        <iframe id="preview-frame" src="{{ route('ecommerce.admin.layout.preview') }}"></iframe>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    (() => {
        const input = document.getElementById('section-order');
        const sortableContainer = document.getElementById('sortable-sections');
        
        if (input && sortableContainer && input.value) {
            const orderArr = input.value.split(',').filter(Boolean);
            orderArr.forEach(id => {
                const el = document.querySelector(`details[data-section="${id}"]`);
                if (el) sortableContainer.appendChild(el);
            });
        }

        if (sortableContainer && typeof Sortable !== 'undefined') {
            new Sortable(sortableContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                filter: 'input, textarea, select, button, label, summary::before', // Prevent dragging from input fields
                preventOnFilter: false,
                onEnd: function () {
                    input.value = [...sortableContainer.querySelectorAll('details.section-card')].map(el => el.dataset.section).join(',');
                }
            });
        }

        // AJAX Form Submission
        const form = document.getElementById('layout-form');
        const saveBtn = document.getElementById('save-btn');
        const iframe = document.getElementById('preview-frame');
        const indicator = document.getElementById('save-indicator');
        const syncStatus = document.getElementById('sync-status');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const originalText = saveBtn.innerText;
            saveBtn.innerText = 'Saving...';
            syncStatus.innerText = 'Saving changes...';
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST', // The method is put in _method
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    // Reload iframe
                    syncStatus.innerText = 'Reloading preview...';
                    iframe.src = iframe.src;
                    
                    iframe.onload = () => {
                        syncStatus.innerText = 'Synced';
                        indicator.classList.add('show');
                        setTimeout(() => indicator.classList.remove('show'), 3000);
                    };
                } else {
                    const data = await response.json();
                    alert(data.message || 'Error saving layout');
                    syncStatus.innerText = 'Error';
                }
            } catch (error) {
                alert('Network error while saving');
                syncStatus.innerText = 'Error';
            } finally {
                saveBtn.innerText = originalText;
            }
        });

        // Navigation Links Builder
        const navLinksContainer = document.getElementById('nav-links-container');
        const addNavLinkBtn = document.getElementById('add-nav-link-btn');
        let navLinks = @json($links);

        function saveNavLinksState() {
            document.querySelectorAll('#nav-links-container > div').forEach((item, index) => {
                if (navLinks[index]) {
                    const labelInput = item.querySelector(`input[name="nav_links[${index}][label]"]`);
                    const urlInput = item.querySelector(`input[name="nav_links[${index}][url]"]`);
                    const typeSelect = item.querySelector(`select[name="nav_links[${index}][type]"]`);
                    
                    if (labelInput) navLinks[index].label = labelInput.value;
                    if (urlInput) navLinks[index].url = urlInput.value;
                    if (typeSelect) navLinks[index].type = typeSelect.value;
                    
                    if (navLinks[index].type === 'mega') {
                        const pt = item.querySelector(`input[name="nav_links[${index}][promo_title]"]`);
                        const ps = item.querySelector(`input[name="nav_links[${index}][promo_subtitle]"]`);
                        const pb = item.querySelector(`input[name="nav_links[${index}][promo_button]"]`);
                        const pbu = item.querySelector(`input[name="nav_links[${index}][promo_button_url]"]`);
                        if (pt) navLinks[index].promo_title = pt.value;
                        if (ps) navLinks[index].promo_subtitle = ps.value;
                        if (pb) navLinks[index].promo_button = pb.value;
                        if (pbu) navLinks[index].promo_button_url = pbu.value;
                    }
                }
            });
        }

        function renderNavLinks() {
            navLinksContainer.innerHTML = '';
            navLinks.forEach((link, index) => {
                const item = document.createElement('div');
                item.style.cssText = "background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);";
                
                const typeOptions = `
                    <option value="simple" ${link.type === 'simple' ? 'selected' : ''}>Simple Link</option>
                    <option value="mega" ${link.type === 'mega' ? 'selected' : ''}>Mega Menu</option>
                `;
                
                let megaFields = '';
                if (link.type === 'mega') {
                    megaFields = `
                        <div class="field-grid" style="margin-top:12px;">
                            <label>Promo Title<input name="nav_links[${index}][promo_title]" value="${link.promo_title || ''}" placeholder="e.g. SUMMER SALE"></label>
                            <label>Promo Subtitle<input name="nav_links[${index}][promo_subtitle]" value="${link.promo_subtitle || ''}"></label>
                        </div>
                        <div class="field-grid">
                            <label>Promo Button<input name="nav_links[${index}][promo_button]" value="${link.promo_button || ''}"></label>
                            <label>Button Link<input name="nav_links[${index}][promo_button_url]" value="${link.promo_button_url || ''}"></label>
                        </div>
                    `;
                }

                item.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="font-size:11px; font-weight:bold; color:#5B7A9D; text-transform:uppercase;">Link #${index + 1}</span>
                        <button type="button" class="remove-nav-link" data-index="${index}" style="background:transparent; border:none; color:#DC2626; cursor:pointer; font-size:12px; font-weight:bold;">Remove</button>
                    </div>
                    <div class="field-grid">
                        <label>Label<input name="nav_links[${index}][label]" value="${link.label || ''}" required></label>
                        <label>Type
                            <select class="nav-type-select" data-index="${index}" name="nav_links[${index}][type]" style="background: #FFFFFF; border: 1px solid #E2E8F0; color: #0B1E3D; border-radius: 8px; padding: 12px; margin-top: 8px; width: 100%; outline: none;">
                                ${typeOptions}
                            </select>
                        </label>
                    </div>
                    <label>URL / Hash<input name="nav_links[${index}][url]" value="${link.url || ''}" required></label>
                    ${megaFields}
                `;
                
                navLinksContainer.appendChild(item);
            });

            // Bind events
            document.querySelectorAll('.remove-nav-link').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    saveNavLinksState();
                    const idx = e.target.getAttribute('data-index');
                    navLinks.splice(idx, 1);
                    renderNavLinks();
                });
            });

            document.querySelectorAll('.nav-type-select').forEach(sel => {
                sel.addEventListener('change', (e) => {
                    saveNavLinksState();
                    const idx = e.target.getAttribute('data-index');
                    navLinks[idx].type = e.target.value;
                    renderNavLinks();
                });
            });
            
            // Check max 10
            if (addNavLinkBtn) addNavLinkBtn.style.display = navLinks.length >= 10 ? 'none' : 'block';
        }

        renderNavLinks();

        if (addNavLinkBtn) {
            addNavLinkBtn.addEventListener('click', () => {
                if (navLinks.length < 10) {
                    saveNavLinksState();
                    navLinks.push({ label: 'New Link', type: 'simple', url: '#' });
                    renderNavLinks();
                }
            });
        }

        // Section Button Builders
        @php
            $defaultHeroButtons = !empty($hero['button_label']) ? [['label' => $hero['button_label'], 'url' => $hero['button_url'] ?? '#', 'style' => 'primary']] : [];
            $defaultPromoButtons = !empty($promo['button_label']) ? [['label' => $promo['button_label'], 'url' => $promo['button_url'] ?? '#', 'style' => 'primary']] : [];
        @endphp
        const heroButtons = @json(old('hero_buttons', $hero['buttons'] ?? $defaultHeroButtons));
        const promoButtons = @json(old('promo_buttons', $promo['buttons'] ?? $defaultPromoButtons));

        function initButtonBuilder(containerId, addBtnId, dataArray, inputPrefix) {
            const container = document.getElementById(containerId);
            const addBtn = document.getElementById(addBtnId);
            if (!container || !addBtn) return;

            function saveState() {
                const items = container.querySelectorAll('.button-item');
                items.forEach((item, index) => {
                    const label = item.querySelector(`input[name="${inputPrefix}[${index}][label]"]`);
                    const url = item.querySelector(`input[name="${inputPrefix}[${index}][url]"]`);
                    const style = item.querySelector(`select[name="${inputPrefix}[${index}][style]"]`);
                    if (label) dataArray[index].label = label.value;
                    if (url) dataArray[index].url = url.value;
                    if (style) dataArray[index].style = style.value;
                });
            }

            function render() {
                container.innerHTML = '';
                dataArray.forEach((btn, index) => {
                    const item = document.createElement('div');
                    item.className = 'button-item';
                    item.style.cssText = "background: #FFFFFF; border: 1px solid #E2E8F0; border-left: 3px solid #7BBEF0; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);";
                    
                    item.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:10px; font-weight:800; color:#8BA4C2; text-transform:uppercase; letter-spacing:1px;">Button #${index + 1}</span>
                            <button type="button" class="remove-btn" data-index="${index}" style="background:transparent; border:none; color:#94A3B8; cursor:pointer; padding:4px; border-radius:4px; display:flex; transition:all 0.2s;" onmouseover="this.style.color='#DC2626'; this.style.background='#FEF2F2';" onmouseout="this.style.color='#94A3B8'; this.style.background='transparent';">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" pointer-events="none"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        </div>
                        <div class="field-grid">
                            <label>Label<input name="${inputPrefix}[${index}][label]" value="${btn.label || ''}" required></label>
                            <label>Style
                                <select name="${inputPrefix}[${index}][style]">
                                    <option value="primary" ${btn.style === 'primary' ? 'selected' : ''}>Primary (Solid)</option>
                                    <option value="secondary" ${btn.style === 'secondary' ? 'selected' : ''}>Secondary (Outline)</option>
                                </select>
                            </label>
                        </div>
                        <label>URL / Hash<input name="${inputPrefix}[${index}][url]" value="${btn.url || ''}" required></label>
                    `;
                    container.appendChild(item);
                });

                container.querySelectorAll('.remove-btn').forEach(b => {
                    b.addEventListener('click', (e) => {
                        saveState();
                        dataArray.splice(e.target.getAttribute('data-index'), 1);
                        render();
                    });
                });

                addBtn.style.display = dataArray.length >= 5 ? 'none' : 'block';
            }

            render();

            addBtn.addEventListener('click', () => {
                if (dataArray.length < 5) {
                    saveState();
                    dataArray.push({ label: 'New Button', url: '#', style: 'primary' });
                    render();
                }
            });
        }

        initButtonBuilder('hero-buttons-container', 'add-hero-button-btn', heroButtons, 'hero_buttons');
        initButtonBuilder('promo-buttons-container', 'add-promo-button-btn', promoButtons, 'promo_buttons');

        // Cross-frame sync listener
        window.addEventListener('message', (e) => {
            if (e.data && e.data.action === 'select_section' && e.data.section) {
                const target = document.querySelector(`details[data-section="${e.data.section}"]`);
                if (target) {
                    target.open = true;
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    target.style.transition = 'box-shadow 0.3s, transform 0.3s';
                    target.style.boxShadow = '0 0 0 4px #7BBEF0, 0 8px 24px rgba(27,111,200,0.15)';
                    target.style.transform = 'scale(1.02)';
                    target.style.position = 'relative';
                    target.style.zIndex = '10';
                    
                    setTimeout(() => {
                        target.style.boxShadow = '';
                        target.style.transform = '';
                        target.style.zIndex = '';
                    }, 1200);
                }
            }
        });

    })();
</script>
@endsection
