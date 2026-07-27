@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Careers';
    $pageSubtitle = $pd['subtitle'] ?? 'Join our team';
    $body = $pd['body'] ?? 'We are always looking for talented individuals to join our growing team. Check back soon for open positions.';
    $positions = $pd['open_positions'] ?? [];
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div data-preview-section="wrapper-support-heading-careers" class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="careers-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="careers-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div data-preview-section="wrapper-careers-body" class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <p class="text-gray-300 text-sm leading-relaxed" data-sp-field="careers-body">{{ $body }}</p>
        </div>
    </div>

    @if(count($positions) > 0)
    <div class="space-y-4">
        <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-6">Open Positions</h2>
        @foreach($positions as $pi => $pos)
        <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-6 hover:border-primary/50 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-white font-bold text-lg" data-sp-field="careers-positions-{{ $pi }}-title">{{ $pos['title'] }}</h3>
                    <p class="text-gray-400 text-sm" data-sp-field="careers-positions-{{ $pi }}-location">{{ $pos['location'] ?? '' }}</p>
                </div>
                <span class="text-primary font-bold text-sm">{{ $pos['type'] ?? 'Full-time' }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <p class="text-gray-500 text-sm">No open positions at the moment. Please check back later.</p>
    </div>
    @endif
</div>
@endsection
