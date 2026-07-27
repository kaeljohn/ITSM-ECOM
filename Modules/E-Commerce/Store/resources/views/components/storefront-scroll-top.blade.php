<style>
    /* ── Scroll-to-Top Button Component ── */
    #scroll-top-btn {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 990;
        width: 50px;
        height: 50px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        color: #9CA3AF;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        transform: translateY(80px);
        opacity: 0;
        pointer-events: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    #scroll-top-btn.visible {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }
    #scroll-top-btn:hover {
        background: var(--theme-primary, #ff6b00);
        border-color: var(--theme-primary, #ff6b00);
        color: #fff;
        box-shadow: 0 0 20px rgba(var(--theme-primary-rgb, 255, 107, 0), 0.5);
        transform: translateY(-3px);
    }
    #scroll-top-btn svg {
        width: 22px;
        height: 22px;
        transition: transform 0.3s ease;
    }
    #scroll-top-btn:hover svg {
        transform: translateY(-2px);
    }
</style>

<button id="scroll-top-btn" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="Scroll to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 15l-6-6-6 6"/>
    </svg>
</button>

<script>
(function() {
    'use strict';
    var btn = document.getElementById('scroll-top-btn');
    if (!btn) return;

    function onScroll() {
        btn.classList.toggle('visible', window.scrollY > 300);
    }

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
})();
</script>
