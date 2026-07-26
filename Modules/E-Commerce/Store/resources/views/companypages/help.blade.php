@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Help Center';
    $pageSubtitle = $pd['subtitle'] ?? 'How can we help you?';
    $helpCards = $pd['cards'] ?? [];
    $ctaTitle = $pd['cta_title'] ?? 'Need Immediate Help?';
    $ctaBody = $pd['cta_body'] ?? 'Our support team is available Monday to Saturday, 9 AM to 6 PM.';
    $ctaButton = $pd['cta_button'] ?? 'Start Live Chat';
    $phIconMap = ['truck' => 'ph-truck', 'arrow-arc-left' => 'ph-arrow-arc-left', 'question' => 'ph-question', 'headset' => 'ph-headset', 'envelope' => 'ph-envelope', 'phone' => 'ph-phone', 'map-pin' => 'ph-map-pin', 'chat-circle-text' => 'ph-chat-circle-text', 'chats-teardrop' => 'ph-chats-teardrop', 'medal' => 'ph-medal', 'shield-check' => 'ph-shield-check', 'rocket' => 'ph-rocket'];
    $routeUrls = ['pages.shipping' => '/shipping', 'pages.returns' => '/returns', 'pages.faq' => '/faq', 'pages.contact' => '/contact'];
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="help-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="help-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-20">
        @foreach($helpCards as $ci => $card)
        <a href="{{ $routeUrls[$card['route'] ?? 'pages.contact'] ?? '/contact' }}" class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 hover:border-primary/50 hover:shadow-glow-lg transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center mb-4 group-hover:bg-primary/30 transition-colors">
                <i class="{{ $phIconMap[$card['icon']] ?? 'ph-question' }} text-2xl text-primary"></i>
            </div>
            <h3 class="text-white text-xl font-black uppercase tracking-wide mb-2 group-hover:text-primary transition-colors" data-sp-field="help-cards-{{ $ci }}-title">{{ $card['title'] }}</h3>
            <p class="text-gray-400 text-sm font-medium leading-relaxed" data-sp-field="help-cards-{{ $ci }}-description">{{ $card['description'] }}</p>
        </a>
        @endforeach
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-12 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center mx-auto mb-6">
                <i class="ph ph-chats-teardrop text-3xl text-primary"></i>
            </div>
            <h3 class="text-white text-2xl font-black uppercase tracking-wide mb-3" data-sp-field="help-cta_title">{{ $ctaTitle }}</h3>
            <p class="text-gray-400 text-sm font-medium max-w-md mx-auto mb-8" data-sp-field="help-cta_body">{{ $ctaBody }}</p>
            <a href="#" class="inline-flex items-center gap-3 bg-primary hover:bg-white text-white hover:text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 shadow-glow hover:shadow-glow-lg" data-sp-field="help-cta_button">
                <i class="ph ph-chat-circle-text text-lg"></i> {{ $ctaButton }}
            </a>
        </div>
    </div>
</div>
@endsection
