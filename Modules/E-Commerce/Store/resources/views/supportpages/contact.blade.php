@php
    $pd = $pageData;
    $pageTitle = $pd['title'] ?? 'Contact & FAQ';
    $pageSubtitle = $pd['subtitle'] ?? "We'd love to hear from you";
    $contactCards = $pd['cards'] ?? [];
    $faqTitle = $pd['faq_title'] ?? 'Frequently Asked Questions';
    $faqItems = $pd['faq_items'] ?? [];
    $phIconMap = ['truck' => 'ph-truck', 'arrow-arc-left' => 'ph-arrow-arc-left', 'question' => 'ph-question', 'headset' => 'ph-headset', 'envelope' => 'ph-envelope', 'phone' => 'ph-phone', 'map-pin' => 'ph-map-pin', 'chat-circle-text' => 'ph-chat-circle-text', 'chats-teardrop' => 'ph-chats-teardrop', 'medal' => 'ph-medal', 'shield-check' => 'ph-shield-check', 'rocket' => 'ph-rocket'];
@endphp

@extends('ecommerce::layouts.page')

@section('title', $pageTitle . ' — ' . $storefrontName)

@section('page-content')
<div class="max-w-4xl mx-auto">
    <div data-preview-section="wrapper-support-heading-contact" class="text-center mb-16">
        <h1 class="text-5xl sm:text-6xl font-black text-white uppercase tracking-tight leading-none mb-6" data-sp-field="contact-title">{{ $pageTitle }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto" data-sp-field="contact-subtitle">{{ $pageSubtitle }}</p>
    </div>

    <div data-preview-section="wrapper-support-contact-cards" class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-16">
        @foreach($contactCards as $ci => $card)
        <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 text-center hover:border-primary/50 transition-all duration-300">
            <div class="w-14 h-14 rounded-xl bg-primary/20 flex items-center justify-center mx-auto mb-5">
                <i class="{{ $phIconMap[$card['icon']] ?? 'ph-envelope' }} text-2xl text-primary"></i>
            </div>
            <h3 class="text-white text-lg font-black uppercase tracking-wide mb-2" data-sp-field="contact-cards-{{ $ci }}-title">{{ $card['title'] }}</h3>
            <p class="text-gray-400 text-sm font-medium" data-sp-field="contact-cards-{{ $ci }}-detail">{{ $card['detail'] }}</p>
            @if(!empty($card['sub']))
            <p class="text-gray-500 text-xs mt-2" data-sp-field="contact-cards-{{ $ci }}-sub">{{ $card['sub'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    <div data-preview-section="wrapper-support-faq-items">
    @if(count($faqItems) > 0)
    <div class="text-center mb-12">
        <h2 class="text-3xl sm:text-4xl font-black text-white uppercase tracking-tight leading-none mb-4" data-sp-field="contact-faq_title">{{ $faqTitle }}</h2>
    </div>

    <div class="space-y-4 mb-16">
        @foreach($faqItems as $index => $item)
            <details class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 group open:border-primary/50 transition-all duration-300 overflow-hidden">
                <summary class="flex items-center justify-between p-6 sm:p-8 cursor-pointer list-none select-none hover:bg-white/5 transition-colors">
                    <span class="text-white font-bold text-sm sm:text-base tracking-wide pr-4" data-sp-field="contact-faq_items-{{ $index }}-q">{{ $item['q'] }}</span>
                    <span class="text-primary shrink-0 transition-transform duration-300 group-open:rotate-180">
                        <i class="ph ph-caret-down text-xl"></i>
                    </span>
                </summary>
                <div class="px-6 sm:px-8 pb-6 sm:pb-8 border-t border-white/5 pt-5">
                    <p class="text-gray-400 text-sm leading-relaxed" data-sp-field="contact-faq_items-{{ $index }}-a">{{ $item['a'] }}</p>
                </div>
            </details>
        @endforeach
    </div>
    @endif
    </div>

    <div class="liquid-glass bg-black/40 backdrop-blur-2xl rounded-2xl border border-white/5 p-8 sm:p-12 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <h2 class="text-white text-2xl font-black uppercase tracking-wide mb-8 text-center">Send Us a Message</h2>
            <form class="space-y-5 max-w-lg mx-auto" onsubmit="alert('Message feature coming soon! Please email us directly.'); return false;">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Name</label>
                        <input type="text" placeholder="Your name" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Email</label>
                        <input type="email" placeholder="your@email.com" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Subject</label>
                    <input type="text" placeholder="How can we help?" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Message</label>
                    <textarea rows="5" placeholder="Tell us more about your inquiry..." class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-white text-white hover:text-black py-3.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all duration-300 shadow-glow hover:shadow-glow-lg flex items-center justify-center gap-3">
                    <i class="ph ph-paper-plane-right text-lg"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
