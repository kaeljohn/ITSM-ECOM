@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Returns & Exchanges';
    $pageSubtitle = $pd['subtitle'] ?? 'Your satisfaction is our priority';
    $warrantyTitle = $pd['warranty_title'] ?? 'Warranty Coverage';
    $warrantySub = $pd['warranty_sub'] ?? 'Coverage terms vary by product';
    $warrantyBody = $pd['warranty_body'] ?? 'All products are covered by a warranty against manufacturer defects.';
    $policy = $pd['policy'] ?? [];
    $processTitle = $pd['process_title'] ?? 'Return Process';
    $processSub = $pd['process_sub'] ?? 'How to initiate a return';
    $steps = $pd['steps'] ?? [];

    $bgColorMap = ['green' => 'bg-green-500/20', 'yellow' => 'bg-yellow-500/20', 'red' => 'bg-red-500/20'];
    $iconColorMap = ['green' => 'text-green-400', 'yellow' => 'text-yellow-400', 'red' => 'text-red-400'];
    $phCheck = 'ph-check'; $phWarning = 'ph-warning'; $phX = 'ph-x';
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="returns-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="returns-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-14 h-14 rounded-xl bg-primary/20 flex items-center justify-center">
                    <i class="ph ph-shield-check text-3xl text-primary"></i>
                </div>
                <div>
                    <h2 class="text-white text-2xl font-black uppercase tracking-wide" data-sp-field="returns-warranty_title">{{ $warrantyTitle }}</h2>
                    <p class="text-gray-400 text-sm" data-sp-field="returns-warranty_sub">{{ $warrantySub }}</p>
                </div>
            </div>
            <p class="text-gray-400 text-sm leading-relaxed" data-sp-field="returns-warranty_body">{{ $warrantyBody }}</p>
        </div>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 mb-6">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-14 h-14 rounded-xl bg-primary/20 flex items-center justify-center">
                <i class="ph ph-arrow-arc-left text-3xl text-primary"></i>
            </div>
            <div>
                <h2 class="text-white text-2xl font-black uppercase tracking-wide">Return Policy</h2>
                <p class="text-gray-400 text-sm">30-day return window for most items</p>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($policy as $pi => $item)
            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/5">
                <div class="w-8 h-8 rounded-lg {{ $bgColorMap[$item['color']] ?? 'bg-white/10' }} flex items-center justify-center shrink-0 mt-0.5">
                    <i class="{{ $item['icon'] === 'check' ? $phCheck : ($item['icon'] === 'warning' ? $phWarning : $phX) }} {{ $iconColorMap[$item['color']] ?? 'text-gray-400' }} text-sm"></i>
                </div>
                <div>
                    <p class="text-white text-sm font-bold" data-sp-field="returns-policy-{{ $pi }}-title">{{ $item['title'] }}</p>
                    <p class="text-gray-400 text-xs" data-sp-field="returns-policy-{{ $pi }}-desc">{{ $item['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-14 h-14 rounded-xl bg-primary/20 flex items-center justify-center">
                <i class="ph ph-clipboard-text text-3xl text-primary"></i>
            </div>
            <div>
                <h2 class="text-white text-2xl font-black uppercase tracking-wide">{{ $processTitle }}</h2>
                <p class="text-gray-400 text-sm">{{ $processSub }}</p>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($steps as $si => $step)
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center shrink-0 text-primary font-black">{{ $step['num'] }}</div>
                <div>
                    <p class="text-white text-sm font-bold" data-sp-field="returns-steps-{{ $si }}-title">{{ $step['title'] }}</p>
                    <p class="text-gray-400 text-xs" data-sp-field="returns-steps-{{ $si }}-desc">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
