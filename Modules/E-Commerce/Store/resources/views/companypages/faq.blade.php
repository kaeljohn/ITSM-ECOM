@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'FAQ';
    $pageSubtitle = $pd['subtitle'] ?? 'Frequently Asked Questions';
    $faqItems = $pd['items'] ?? [];
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="faq-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="faq-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div class="space-y-4">
        @foreach($faqItems as $index => $item)
            <details class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 group open:border-primary/50 transition-all duration-300 overflow-hidden">
                <summary class="flex items-center justify-between p-6 sm:p-8 cursor-pointer list-none select-none hover:bg-white/5 transition-colors">
                    <span class="text-white font-bold text-sm sm:text-base tracking-wide pr-4" data-sp-field="faq-items-{{ $index }}-q">{{ $item['q'] }}</span>
                    <span class="text-primary shrink-0 transition-transform duration-300 group-open:rotate-180">
                        <i class="ph ph-caret-down text-xl"></i>
                    </span>
                </summary>
                <div class="px-6 sm:px-8 pb-6 sm:pb-8 border-t border-white/5 pt-5">
                    <p class="text-gray-400 text-sm leading-relaxed" data-sp-field="faq-items-{{ $index }}-a">{{ $item['a'] }}</p>
                </div>
            </details>
        @endforeach
    </div>

    <div class="text-center mt-16 py-12 border-t border-white/5">
        <h3 class="text-white text-xl font-black uppercase tracking-wide mb-3">Still Have Questions?</h3>
        <p class="text-gray-400 text-sm max-w-md mx-auto mb-8">Our support team is ready to help you.</p>
        <a href="/contact" class="inline-flex items-center gap-3 bg-primary hover:bg-white text-white hover:text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 shadow-glow hover:shadow-glow-lg">
            <i class="ph ph-headset text-lg"></i> Contact Support
        </a>
    </div>
</div>
@endsection
