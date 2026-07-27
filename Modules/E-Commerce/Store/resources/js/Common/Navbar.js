// Search logic
const searchContainer = document.getElementById('search-container');
const searchInput = document.getElementById('search-input');
const searchDropdown = document.getElementById('search-dropdown');
const cartDropdown = document.getElementById('cart-dropdown');
const searchClear = document.getElementById('search-clear');
const searchOverlay = document.getElementById('search-overlay');

if (searchInput && searchDropdown) {
    // Save the initial Blade-rendered HTML (real listings from DB)
    const initialSearchHtml = searchDropdown.innerHTML;

    // Fetch live listings from API to rebuild the dropdown
    function fetchSuggestedListings() {
        return fetch('/ecommerce-admin/crm/api/suggested-listings')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (data && data.length > 0) {
                    var html = '<div class="px-5 mb-2"><span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Suggested Products</span></div><ul class="text-sm text-gray-300 flex flex-col">';
                    data.forEach(function(item) {
                        var imgHtml = item.image_url
                            ? '<img src="' + item.image_url + '" alt="" loading="lazy" class="lazy-img w-7 h-7 rounded object-cover">'
                            : '<i class="ph ph-package text-gray-500"></i>';
                        var price = 'P' + Number(item.price).toLocaleString();
                        html += '<li><a href="' + item.url + '" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors">'
                            + imgHtml
                            + '<span class="flex-1 truncate">' + escapeHtml(item.name) + '</span>'
                            + '<span class="text-xs text-gray-500 font-bold">' + price + '</span>'
                            + '</a></li>';
                    });
                    html += '</ul>';
                    return html;
                }
                return null;
            })
            .catch(function() { return null; });
    }

    // Cache the live suggestions HTML so we don't re-fetch every focus
    var cachedLiveHtml = null;

    function getDefaultHtml() {
        return cachedLiveHtml || initialSearchHtml;
    }

    // Prime the cache once
    fetchSuggestedListings().then(function(html) {
        if (html) cachedLiveHtml = html;
    });

    searchInput.addEventListener('focus', function() {
        if (window.lenis) window.lenis.stop();

        if (cartDropdown && !cartDropdown.classList.contains('opacity-0')) {
            cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
        }

        // Show search dropdown when focused, even if empty
        searchDropdown.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
        searchDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');

        if (searchInput.value.trim().length === 0) {
            searchDropdown.innerHTML = getDefaultHtml();
        }

        if (searchOverlay) {
            searchOverlay.classList.remove('opacity-0', 'pointer-events-none');
            searchOverlay.classList.add('opacity-100', 'pointer-events-auto');
        }
    });

    var debounceTimer;
    searchInput.addEventListener('input', function() {
        var query = searchInput.value.toLowerCase().trim();

        if (query.length > 0) {
            searchClear.classList.remove('opacity-0', 'pointer-events-none');
            searchClear.classList.add('opacity-100', 'pointer-events-auto');

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetch('/api/search/suggestions?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.ok ? r.json() : []; })
                    .then(function(results) {
                        if (results.length > 0) {
                            var html = '<ul class="text-sm text-gray-300 flex flex-col">';
                            results.forEach(function(item) {
                                html += '<li><a href="/search?q=' + encodeURIComponent(item.name)
                                    + '" class="flex items-center justify-between px-4 py-2 hover:bg-white/5 transition-colors group">'
                                    + '<div class="flex items-center gap-3">'
                                    + '<i class="ph ph-magnifying-glass text-primary text-lg group-hover:scale-110 transition-transform"></i>'
                                    + '<div class="flex flex-col">'
                                    + '<span class="text-gray-200 font-medium text-sm">' + escapeHtml(item.name) + '</span>'
                                    + '<span class="text-gray-500 font-light text-[10px] uppercase">' + (item.type || 'Product') + '</span>'
                                    + '</div></div>'
                                    + '<span class="text-primary font-bold text-xs">' + (item.price || '') + '</span>'
                                    + '</a></li>';
                            });
                            html += '</ul>';
                            searchDropdown.innerHTML = html;
                        } else {
                            searchDropdown.innerHTML = '<ul class="text-sm text-gray-300 flex flex-col"><li class="px-4 py-4 text-gray-500 text-sm text-center">No products found for "' + query + '"</li></ul>';
                        }
                    })
                    .catch(function() {
                        console.error('Error fetching search suggestions');
                    });
            }, 300);

        } else {
            searchClear.classList.remove('opacity-100', 'pointer-events-auto');
            searchClear.classList.add('opacity-0', 'pointer-events-none');
            searchDropdown.innerHTML = getDefaultHtml();
        }
    });

    if (searchClear) {
        searchClear.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = '';
            searchClear.classList.remove('opacity-100', 'pointer-events-auto');
            searchClear.classList.add('opacity-0', 'pointer-events-none');
            searchDropdown.innerHTML = getDefaultHtml();
            searchInput.focus();
        });
    }

    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target)) {
            searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');

            if (searchOverlay) {
                searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                searchOverlay.classList.add('opacity-0', 'pointer-events-none');
            }

            if (window.lenis) window.lenis.start();
        }
    });
}

// Helper
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Cart Dropdown Logic
const cartContainer = document.getElementById('cart-container');
const cartBtn = document.getElementById('cart-btn');

if (cartContainer && cartBtn && cartDropdown) {
    cartBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var isClosed = cartDropdown.classList.contains('opacity-0');

        if (isClosed) {
            if (searchDropdown && !searchDropdown.classList.contains('opacity-0')) {
                searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');

                if (searchOverlay) {
                    searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                    searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }

            cartDropdown.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
            cartDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
        } else {
            cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
        }
    });

    document.addEventListener('click', function(e) {
        if (!cartContainer.contains(e.target)) {
            cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
        }
    });
}

// Gaming PCs Dropdown Logic
const gamingPcsContainer = document.getElementById('gaming-pcs-container');
const gamingPcsBtn = document.getElementById('gaming-pcs-btn');
const gamingPcsDropdown = document.getElementById('gaming-pcs-dropdown');
const gamingPcsIcon = document.getElementById('gaming-pcs-icon');
const navOverlay = document.getElementById('nav-overlay');

if (gamingPcsBtn && gamingPcsDropdown && navOverlay) {
    gamingPcsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var isOpen = !gamingPcsDropdown.classList.contains('opacity-0');

        if (isOpen) {
            gamingPcsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            gamingPcsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
            navOverlay.classList.add('opacity-0', 'pointer-events-none');
            gamingPcsIcon.classList.remove('rotate-180', 'text-primary');
            gamingPcsBtn.classList.remove('text-primary');

            if (!searchDropdown || searchDropdown.classList.contains('opacity-0')) {
                if (window.lenis) window.lenis.start();
            }
        } else {
            if (searchDropdown && !searchDropdown.classList.contains('opacity-0')) {
                searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                if (searchOverlay) {
                    searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                    searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }
            if (cartDropdown && !cartDropdown.classList.contains('opacity-0')) {
                cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
            }
            if (typeof gamingLaptopsDropdown !== 'undefined' && gamingLaptopsDropdown && !gamingLaptopsDropdown.classList.contains('opacity-0')) {
                gamingLaptopsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingLaptopsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingLaptopsIcon) gamingLaptopsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingLaptopsBtn) gamingLaptopsBtn.classList.remove('text-primary');
            }
            if (typeof partsDropdown !== 'undefined' && partsDropdown && !partsDropdown.classList.contains('opacity-0')) {
                partsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                partsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (partsIcon) partsIcon.classList.remove('rotate-180', 'text-primary');
                if (partsBtn) partsBtn.classList.remove('text-primary');
            }
            if (typeof forgeStoreDropdown !== 'undefined' && forgeStoreDropdown && !forgeStoreDropdown.classList.contains('opacity-0')) {
                forgeStoreDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                forgeStoreDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (typeof forgeStoreIcon !== 'undefined' && forgeStoreIcon) forgeStoreIcon.classList.remove('rotate-180', 'text-primary');
                if (typeof forgeStoreBtn !== 'undefined' && forgeStoreBtn) forgeStoreBtn.classList.remove('text-primary');
            }

            gamingPcsDropdown.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            gamingPcsDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
            navOverlay.classList.remove('opacity-0', 'pointer-events-none');
            navOverlay.classList.add('opacity-100', 'pointer-events-auto');
            gamingPcsIcon.classList.add('rotate-180', 'text-primary');
            gamingPcsBtn.classList.add('text-primary');
            if (window.lenis) window.lenis.stop();
        }
    });

    document.addEventListener('click', function(e) {
        if (!gamingPcsContainer.contains(e.target)) {
            if (!gamingPcsDropdown.classList.contains('opacity-0')) {
                gamingPcsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingPcsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                navOverlay.classList.add('opacity-0', 'pointer-events-none');
                gamingPcsIcon.classList.remove('rotate-180', 'text-primary');
                gamingPcsBtn.classList.remove('text-primary');

                if (!searchOverlay || searchOverlay.classList.contains('opacity-0')) {
                    if (window.lenis) window.lenis.start();
                }
            }
        }
    });
}

// Gaming Laptops Dropdown Logic
const gamingLaptopsContainer = document.getElementById('gaming-laptops-container');
const gamingLaptopsBtn = document.getElementById('gaming-laptops-btn');
const gamingLaptopsDropdown = document.getElementById('gaming-laptops-dropdown');
const gamingLaptopsIcon = document.getElementById('gaming-laptops-icon');

if (gamingLaptopsBtn && gamingLaptopsDropdown && navOverlay) {
    gamingLaptopsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var isOpen = !gamingLaptopsDropdown.classList.contains('opacity-0');

        if (isOpen) {
            gamingLaptopsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            gamingLaptopsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
            navOverlay.classList.add('opacity-0', 'pointer-events-none');
            gamingLaptopsIcon.classList.remove('rotate-180', 'text-primary');
            gamingLaptopsBtn.classList.remove('text-primary');

            if (!searchDropdown || searchDropdown.classList.contains('opacity-0')) {
                if (window.lenis) window.lenis.start();
            }
        } else {
            if (searchDropdown && !searchDropdown.classList.contains('opacity-0')) {
                searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                if (searchOverlay) {
                    searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                    searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }
            if (cartDropdown && !cartDropdown.classList.contains('opacity-0')) {
                cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
            }
            if (gamingPcsDropdown && !gamingPcsDropdown.classList.contains('opacity-0')) {
                gamingPcsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingPcsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingPcsIcon) gamingPcsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingPcsBtn) gamingPcsBtn.classList.remove('text-primary');
            }
            if (typeof partsDropdown !== 'undefined' && partsDropdown && !partsDropdown.classList.contains('opacity-0')) {
                partsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                partsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (partsIcon) partsIcon.classList.remove('rotate-180', 'text-primary');
                if (partsBtn) partsBtn.classList.remove('text-primary');
            }
            if (typeof forgeStoreDropdown !== 'undefined' && forgeStoreDropdown && !forgeStoreDropdown.classList.contains('opacity-0')) {
                forgeStoreDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                forgeStoreDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (typeof forgeStoreIcon !== 'undefined' && forgeStoreIcon) forgeStoreIcon.classList.remove('rotate-180', 'text-primary');
                if (typeof forgeStoreBtn !== 'undefined' && forgeStoreBtn) forgeStoreBtn.classList.remove('text-primary');
            }

            gamingLaptopsDropdown.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            gamingLaptopsDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
            navOverlay.classList.remove('opacity-0', 'pointer-events-none');
            navOverlay.classList.add('opacity-100', 'pointer-events-auto');
            gamingLaptopsIcon.classList.add('rotate-180', 'text-primary');
            gamingLaptopsBtn.classList.add('text-primary');
            if (window.lenis) window.lenis.stop();
        }
    });

    document.addEventListener('click', function(e) {
        if (!gamingLaptopsContainer.contains(e.target)) {
            if (!gamingLaptopsDropdown.classList.contains('opacity-0')) {
                gamingLaptopsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingLaptopsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                navOverlay.classList.add('opacity-0', 'pointer-events-none');
                gamingLaptopsIcon.classList.remove('rotate-180', 'text-primary');
                gamingLaptopsBtn.classList.remove('text-primary');

                if (!searchOverlay || searchOverlay.classList.contains('opacity-0')) {
                    if (window.lenis) window.lenis.start();
                }
            }
        }
    });
}

// Parts & Accessories Dropdown Logic
const partsContainer = document.getElementById('parts-container');
const partsBtn = document.getElementById('parts-btn');
const partsDropdown = document.getElementById('parts-dropdown');
const partsIcon = document.getElementById('parts-icon');

if (partsBtn && partsDropdown && navOverlay) {
    partsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var isOpen = !partsDropdown.classList.contains('opacity-0');

        if (isOpen) {
            partsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            partsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
            navOverlay.classList.add('opacity-0', 'pointer-events-none');
            partsIcon.classList.remove('rotate-180', 'text-primary');
            partsBtn.classList.remove('text-primary');

            if (!searchDropdown || searchDropdown.classList.contains('opacity-0')) {
                if (window.lenis) window.lenis.start();
            }
        } else {
            if (searchDropdown && !searchDropdown.classList.contains('opacity-0')) {
                searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                if (searchOverlay) {
                    searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                    searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }
            if (cartDropdown && !cartDropdown.classList.contains('opacity-0')) {
                cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
            }
            if (gamingPcsDropdown && !gamingPcsDropdown.classList.contains('opacity-0')) {
                gamingPcsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingPcsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingPcsIcon) gamingPcsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingPcsBtn) gamingPcsBtn.classList.remove('text-primary');
            }
            if (gamingLaptopsDropdown && !gamingLaptopsDropdown.classList.contains('opacity-0')) {
                gamingLaptopsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingLaptopsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingLaptopsIcon) gamingLaptopsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingLaptopsBtn) gamingLaptopsBtn.classList.remove('text-primary');
            }

            partsDropdown.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            partsDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
            navOverlay.classList.remove('opacity-0', 'pointer-events-none');
            navOverlay.classList.add('opacity-100', 'pointer-events-auto');
            partsIcon.classList.add('rotate-180', 'text-primary');
            partsBtn.classList.add('text-primary');
            if (window.lenis) window.lenis.stop();
        }
    });

    document.addEventListener('click', function(e) {
        if (!partsContainer.contains(e.target)) {
            if (!partsDropdown.classList.contains('opacity-0')) {
                partsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                partsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                navOverlay.classList.add('opacity-0', 'pointer-events-none');
                partsIcon.classList.remove('rotate-180', 'text-primary');
                partsBtn.classList.remove('text-primary');

                if (!searchOverlay || searchOverlay.classList.contains('opacity-0')) {
                    if (window.lenis) window.lenis.start();
                }
            }
        }
    });
}

// Forge Store Dropdown Logic
const forgeStoreContainer = document.getElementById('forge-store-container');
const forgeStoreBtn = document.getElementById('forge-store-btn');
const forgeStoreDropdown = document.getElementById('forge-store-dropdown');
const forgeStoreIcon = document.getElementById('forge-store-icon');

if (forgeStoreBtn && forgeStoreDropdown && navOverlay) {
    forgeStoreBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var isOpen = !forgeStoreDropdown.classList.contains('opacity-0');

        if (isOpen) {
            forgeStoreDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            forgeStoreDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
            navOverlay.classList.add('opacity-0', 'pointer-events-none');
            forgeStoreIcon.classList.remove('rotate-180', 'text-primary');
            forgeStoreBtn.classList.remove('text-primary');

            if (!searchDropdown || searchDropdown.classList.contains('opacity-0')) {
                if (window.lenis) window.lenis.start();
            }
        } else {
            if (searchDropdown && !searchDropdown.classList.contains('opacity-0')) {
                searchDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                searchDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                if (searchOverlay) {
                    searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                    searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }
            if (cartDropdown && !cartDropdown.classList.contains('opacity-0')) {
                cartDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                cartDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
            }
            if (typeof gamingPcsDropdown !== 'undefined' && gamingPcsDropdown && !gamingPcsDropdown.classList.contains('opacity-0')) {
                gamingPcsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingPcsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingPcsIcon) gamingPcsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingPcsBtn) gamingPcsBtn.classList.remove('text-primary');
            }
            if (typeof gamingLaptopsDropdown !== 'undefined' && gamingLaptopsDropdown && !gamingLaptopsDropdown.classList.contains('opacity-0')) {
                gamingLaptopsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                gamingLaptopsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (gamingLaptopsIcon) gamingLaptopsIcon.classList.remove('rotate-180', 'text-primary');
                if (gamingLaptopsBtn) gamingLaptopsBtn.classList.remove('text-primary');
            }
            if (typeof forgeStoreDropdown !== 'undefined' && forgeStoreDropdown && !forgeStoreDropdown.classList.contains('opacity-0')) {
                forgeStoreDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                forgeStoreDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (typeof forgeStoreIcon !== 'undefined' && forgeStoreIcon) forgeStoreIcon.classList.remove('rotate-180', 'text-primary');
                if (typeof forgeStoreBtn !== 'undefined' && forgeStoreBtn) forgeStoreBtn.classList.remove('text-primary');
            }
            if (typeof partsDropdown !== 'undefined' && partsDropdown && !partsDropdown.classList.contains('opacity-0')) {
                partsDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                partsDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                if (partsIcon) partsIcon.classList.remove('rotate-180', 'text-primary');
                if (partsBtn) partsBtn.classList.remove('text-primary');
            }

            forgeStoreDropdown.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            forgeStoreDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
            navOverlay.classList.remove('opacity-0', 'pointer-events-none');
            navOverlay.classList.add('opacity-100', 'pointer-events-auto');
            forgeStoreIcon.classList.add('rotate-180', 'text-primary');
            forgeStoreBtn.classList.add('text-primary');
            if (window.lenis) window.lenis.stop();
        }
    });

    document.addEventListener('click', function(e) {
        if (!forgeStoreContainer.contains(e.target)) {
            if (!forgeStoreDropdown.classList.contains('opacity-0')) {
                forgeStoreDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                forgeStoreDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                navOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                navOverlay.classList.add('opacity-0', 'pointer-events-none');
                forgeStoreIcon.classList.remove('rotate-180', 'text-primary');
                forgeStoreBtn.classList.remove('text-primary');

                if (!searchOverlay || searchOverlay.classList.contains('opacity-0')) {
                    if (window.lenis) window.lenis.start();
                }
            }
        }
    });
}

// Cart Add Logic
document.addEventListener('DOMContentLoaded', function() {
    var cartModalHtml = '\
    <div id="cart-modal" class="fixed inset-0 z-[100] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">\
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="cart-modal-backdrop"></div>\
        <div class="relative bg-[#121212] border border-white/10 rounded-2xl p-6 shadow-2xl max-w-sm w-full mx-4 transform scale-95 transition-transform duration-300" id="cart-modal-content">\
            <div class="flex items-start justify-between mb-4">\
                <h3 class="text-xl font-bold text-white flex items-center gap-2">\
                    <i class="ph-fill ph-check-circle text-green-500"></i> Added to Cart\
                </h3>\
                <button id="close-cart-modal" class="text-gray-400 hover:text-white transition-colors">\
                    <i class="ph ph-x text-xl"></i>\
                </button>\
            </div>\
            <div class="flex gap-4 mb-6">\
                <div class="w-16 h-16 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center overflow-hidden shrink-0">\
                    <img id="modal-product-image" src="" alt="Product" class="w-full h-full object-cover hidden">\
                    <i id="modal-product-icon" class="ph-light ph-desktop text-2xl text-gray-500"></i>\
                </div>\
                <div class="flex-1 min-w-0 flex flex-col justify-center">\
                    <p id="modal-product-name" class="text-sm font-bold text-gray-200 mb-1 line-clamp-2"></p>\
                    <p id="modal-product-price" class="text-primary font-black"></p>\
                </div>\
            </div>\
            <div class="flex gap-3">\
                <button id="continue-shopping-btn" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl py-2.5 text-sm font-bold text-white transition-colors">\
                    Continue Shopping\
                </button>\
                <a href="/cart" class="flex-1 bg-gradient-to-r from-primary to-orange-400 hover:from-primary hover:to-orange-500 rounded-xl py-2.5 text-sm font-bold text-white text-center transition-colors shadow-[0_0_15px_rgba(255,107,0,0.3)] flex items-center justify-center">\
                    View Cart\
                </a>\
            </div>\
        </div>\
    </div>';

    document.body.insertAdjacentHTML('beforeend', cartModalHtml);

    var cartModal = document.getElementById('cart-modal');
    var cartModalContent = document.getElementById('cart-modal-content');
    var closeCartModal = document.getElementById('close-cart-modal');
    var continueShoppingBtn = document.getElementById('continue-shopping-btn');
    var cartModalBackdrop = document.getElementById('cart-modal-backdrop');

    function hideModal() {
        cartModal.classList.remove('opacity-100', 'pointer-events-auto');
        cartModal.classList.add('opacity-0', 'pointer-events-none');
        cartModalContent.classList.remove('scale-100');
        cartModalContent.classList.add('scale-95');
        if (window.lenis) window.lenis.start();
    }

    [closeCartModal, continueShoppingBtn, cartModalBackdrop].forEach(function(el) {
        if (el) el.addEventListener('click', hideModal);
    });

    function animateAddToCart(sourceElement) {
        var cartBtnEl = document.getElementById('cart-btn');
        if (!cartBtnEl || !sourceElement) return;

        var sourceRect = sourceElement.getBoundingClientRect();
        var targetRect = cartBtnEl.getBoundingClientRect();

        var flyingEl = document.createElement('div');
        flyingEl.classList.add('fixed', 'bg-primary', 'rounded-full', 'z-[110]', 'pointer-events-none');

        flyingEl.style.width = '16px';
        flyingEl.style.height = '16px';
        flyingEl.style.left = (sourceRect.left + sourceRect.width / 2 - 8) + 'px';
        flyingEl.style.top = (sourceRect.top + sourceRect.height / 2 - 8) + 'px';
        flyingEl.style.transition = 'all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1)';
        flyingEl.style.boxShadow = '0 0 15px rgba(255,107,0,1)';

        document.body.appendChild(flyingEl);

        void flyingEl.offsetWidth;

        flyingEl.style.left = (targetRect.left + targetRect.width / 2 - 8) + 'px';
        flyingEl.style.top = (targetRect.top + targetRect.height / 2 - 8) + 'px';
        flyingEl.style.transform = 'scale(0.3)';
        flyingEl.style.opacity = '0.2';

        setTimeout(function() {
            flyingEl.remove();
        }, 800);
    }

    fetch(window.ecommerce_routes.cart_count)
        .then(function(res) { return res.ok ? res.json() : null; })
        .then(function(data) {
            if (data) updateCartCount(data.cart_count);
        })
        .catch(function(err) { console.error(err); });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.add-to-cart-btn');
        if (!btn) return;

        e.preventDefault();

        var productId = btn.dataset.productId || 'mock-' + Math.floor(Math.random() * 1000);
        var name = btn.dataset.name || 'Mock Product';
        var priceStr = btn.dataset.price || '0';
        var price = parseFloat(priceStr.replace(/[^0-9.-]+/g, ""));
        var imageUrl = btn.dataset.image || '';
        var productType = btn.dataset.productType || 'generic';
        var configuration = btn.dataset.configuration || null;

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        btn.classList.add('opacity-50', 'cursor-wait');

        fetch(window.ecommerce_routes.cart_add, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                name: name,
                price: price,
                quantity: 1,
                image_url: imageUrl,
                product_type: productType,
                configuration: configuration
            })
        })
        .then(function(response) {
            if (!response.ok) {
                return response.json().then(function(errorData) {
                    throw errorData;
                }).catch(function() {
                    throw new Error('Server returned an error: ' + response.status);
                });
            }
            return response.json();
        })
        .then(function(data) {
            btn.classList.remove('opacity-50', 'cursor-wait');
            if (data.success) {
                animateAddToCart(btn);

                setTimeout(function() {
                    updateCartCount(data.cart_count);

                    document.getElementById('modal-product-name').textContent = name;
                    document.getElementById('modal-product-price').textContent = 'P' + price.toLocaleString();

                    var imgEl = document.getElementById('modal-product-image');
                    var iconEl = document.getElementById('modal-product-icon');
                    if (imageUrl) {
                        imgEl.src = imageUrl;
                        imgEl.classList.remove('hidden');
                        iconEl.classList.add('hidden');
                    } else {
                        imgEl.classList.add('hidden');
                        iconEl.classList.remove('hidden');
                    }

                    cartModal.classList.remove('opacity-0', 'pointer-events-none');
                    cartModal.classList.add('opacity-100', 'pointer-events-auto');
                    cartModalContent.classList.remove('scale-95');
                    cartModalContent.classList.add('scale-100');
                    if (window.lenis) window.lenis.stop();
                }, 400);
            }
        })
        .catch(function(err) {
            console.error('Error adding product to cart:', err);
            btn.classList.remove('opacity-50', 'cursor-wait');
        });
    });

    function updateCartCount(count) {
        var cartBadges = document.querySelectorAll('#cart-btn > div.relative > span.bg-primary');
        cartBadges.forEach(function(badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
                badge.classList.add('flex');

                badge.classList.remove('animate-ping');
                void badge.offsetWidth;
                badge.classList.add('animate-pulse');
                setTimeout(function() { badge.classList.remove('animate-pulse'); }, 1000);
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }
        });
    }
});
