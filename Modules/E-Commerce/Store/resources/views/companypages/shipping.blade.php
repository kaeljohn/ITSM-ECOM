@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Shipping & Delivery';
    $pageSubtitle = $pd['subtitle'] ?? 'Everything you need to know about shipping';
    $rates = $pd['rates'] ?? [];
    $processing = $pd['processing'] ?? [];
    $trackingBody = $pd['tracking_body'] ?? 'Once your order ships, you will receive a confirmation email with a tracking number. You can also track your order directly from your account dashboard.';
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="shipping-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="shipping-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-8">Shipping Rates</h2>
            <div class="space-y-4">
                @foreach($rates as $ri => $rate)
                <div class="flex items-center justify-between p-4 rounded-xl {{ !empty($rate['highlighted']) ? 'bg-primary/10 border border-primary/20' : 'bg-white/5 border border-white/5' }}">
                    <div>
                        <span class="text-white font-bold" data-sp-field="shipping-rates-{{ $ri }}-label">{{ $rate['label'] }}</span>
                        <p class="text-gray-500 text-xs mt-0.5" data-sp-field="shipping-rates-{{ $ri }}-desc">{{ $rate['desc'] }}</p>
                    </div>
                    <span class="{{ !empty($rate['highlighted']) ? 'text-green-400' : 'text-primary' }} font-black" data-sp-field="shipping-rates-{{ $ri }}-price">{{ $rate['price'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6">
        <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-6">Processing Time</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            @foreach($processing as $pi => $proc)
            <div class="p-4 rounded-xl bg-white/5 border border-white/5">
                <div class="text-primary font-black text-lg mb-1" data-sp-field="shipping-processing-{{ $pi }}-label">{{ $proc['label'] }}</div>
                <p class="text-gray-400 text-xs font-medium" data-sp-field="shipping-processing-{{ $pi }}-desc">{{ $proc['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12">
        <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-6">Order Tracking</h2>
        <p class="text-gray-400 text-sm leading-relaxed mb-6" data-sp-field="shipping-tracking_body">{{ $trackingBody }}</p>
        <div class="flex items-center gap-4 p-4 rounded-xl bg-white/5 border border-white/5">
            <i class="ph ph-truck text-2xl text-primary"></i>
            <div>
                <p class="text-white text-sm font-bold">Need help tracking your order?</p>
                <a href="/contact" class="text-primary text-xs font-bold hover:text-white transition-colors">Contact Support →</a>
            </div>
        </div>
    </div>
</div>
@endsection
