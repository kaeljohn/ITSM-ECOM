<?php

return [
    // Public stores resolve their client from {client}.shop.section4.tech.
    // This must match the wildcard domain configured in DigitalOcean and DNS.
    'storefront_base_domain' => env('ECOMMERCE_STOREFRONT_BASE_DOMAIN', 'shop.section4.tech'),

    // When enabled, the storefront routes will also be accessible without a subdomain
    // prefix. This is useful for local development where you cannot set up wildcard
    // DNS. Set to true for local testing only.
    'localhost_fallback' => env('ECOMMERCE_LOCALHOST_FALLBACK', false),
];
