@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'About Us';
    $pageSubtitle = $pd['subtitle'] ?? 'Learn more about ' . ($storefrontName ?? 'our store');
    $story = $pd['story'] ?? "Welcome to our store! We are dedicated to bringing you the best products and an exceptional shopping experience.";
    $values = $pd['values'] ?? [];
    $ctaLabel = $pd['cta_label'] ?? 'Browse Store';
    $phIconMap = ['truck' => 'ph-truck', 'arrow-arc-left' => 'ph-arrow-arc-left', 'question' => 'ph-question', 'headset' => 'ph-headset', 'envelope' => 'ph-envelope', 'phone' => 'ph-phone', 'map-pin' => 'ph-map-pin', 'chat-circle-text' => 'ph-chat-circle-text', 'chats-teardrop' => 'ph-chats-teardrop', 'medal' => 'ph-medal', 'shield-check' => 'ph-shield-check', 'rocket' => 'ph-rocket'];
    $storyParagraphs = explode("\n\n", $story);
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div data-preview-section="wrapper-support-heading-about" class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="about-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="about-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div data-preview-section="wrapper-support-story" class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-6">Our Story</h2>
            <div class="space-y-4 text-gray-300 text-sm leading-relaxed" data-sp-field="about-story">
                @foreach($storyParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        @foreach($values as $vi => $val)
        <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 text-center">
            <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center mx-auto mb-4">
                <i class="{{ $phIconMap[$val['icon']] ?? 'ph-star' }} text-2xl text-primary"></i>
            </div>
            <h3 class="text-white text-lg font-black uppercase tracking-wide mb-2" data-sp-field="about-values-{{ $vi }}-title">{{ $val['title'] }}</h3>
            <p class="text-gray-400 text-xs font-medium" data-sp-field="about-values-{{ $vi }}-description">{{ $val['description'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="text-center py-12">        <a href="{{ url('/') }}" class="inline-flex items-center gap-3 bg-primary hover:bg-white text-white hover:text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 shadow-glow hover:shadow-glow-lg" data-sp-field="about-cta_label">
                <i class="ph ph-storefront text-lg"></i> {{ $ctaLabel }}
            </a>
    </div>
</div>
@endsection
