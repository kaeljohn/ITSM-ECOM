@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Affiliates';
    $pageSubtitle = $pd['subtitle'] ?? 'Partner with us';
    $body = $pd['body'] ?? 'Join our affiliate program and earn commissions by promoting our products.';
    $benefits = $pd['benefits'] ?? [];
    $ctaLabel = $pd['cta_label'] ?? 'Apply Now';
    $phIconMap = ['truck' => 'ph-truck', 'arrow-arc-left' => 'ph-arrow-arc-left', 'question' => 'ph-question', 'headset' => 'ph-headset', 'envelope' => 'ph-envelope', 'phone' => 'ph-phone', 'map-pin' => 'ph-map-pin', 'chat-circle-text' => 'ph-chat-circle-text', 'chats-teardrop' => 'ph-chats-teardrop', 'medal' => 'ph-medal', 'shield-check' => 'ph-shield-check', 'rocket' => 'ph-rocket'];
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="affiliates-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="affiliates-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <p class="text-gray-300 text-sm leading-relaxed" data-sp-field="affiliates-body">{{ $body }}</p>
        </div>
    </div>

    @if(count($benefits) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        @foreach($benefits as $bi => $ben)
        <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 text-center">
            <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center mx-auto mb-4">
                <i class="{{ $phIconMap[$ben['icon']] ?? 'ph-star' }} text-2xl text-primary"></i>
            </div>
            <h3 class="text-white text-lg font-black uppercase tracking-wide mb-2" data-sp-field="affiliates-benefits-{{ $bi }}-title">{{ $ben['title'] }}</h3>
            <p class="text-gray-400 text-xs font-medium" data-sp-field="affiliates-benefits-{{ $bi }}-description">{{ $ben['description'] }}</p>
        </div>
        @endforeach
    </div>
    @endif

    <div class="text-center py-8">
        <a href="#" class="inline-flex items-center gap-3 bg-primary hover:bg-white text-white hover:text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 shadow-glow hover:shadow-glow-lg" data-sp-field="affiliates-cta_label">
            <i class="ph ph-handshake text-lg"></i> {{ $ctaLabel }}
        </a>
    </div>
</div>
@endsection
