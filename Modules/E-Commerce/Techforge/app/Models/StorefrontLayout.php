<?php

namespace Modules\Ecommerce\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\Concerns\BelongsToClient;

class StorefrontLayout extends Model
{
    use BelongsToClient;

    protected $fillable = ['draft_layout', 'published_layout'];

    protected $casts = [
        'draft_layout' => 'array',
        'published_layout' => 'array',
    ];

    public static function defaultFor(Company $company): array
    {
        return [
            'brand_name' => $company->company_name,
            'tagline' => 'Official Nexora storefront',
            'primary_color' => '#ff6b00',
            'accent_color' => '#f59e0b',
            'logo_path' => null,
            'custom_pages' => [
                ['id' => 'accessories', 'slug' => 'store/accessories', 'title' => 'Accessories', 'blueprint' => 'accessories'],
                ['id' => 'monitors', 'slug' => 'store/monitors', 'title' => 'Monitors', 'blueprint' => 'monitors'],
                ['id' => 'pc-parts', 'slug' => 'store/pc-parts', 'title' => 'PC Parts', 'blueprint' => 'pc-parts'],
                ['id' => 'gaming-laptops', 'slug' => 'gaming-laptops', 'title' => 'Gaming Laptops', 'blueprint' => 'gaming-laptops'],
                ['id' => 'prebuilt-pcs', 'slug' => 'prebuilt-pcs', 'title' => 'Prebuilt PCs', 'blueprint' => 'prebuilt-pcs'],
            ],
            'sections' => [
                [
                    'id' => 'hero',
                    'enabled' => true,
                    'title' => 'Products built for your next big move.',
                    'highlight' => 'Shop with confidence.',
                    'body' => 'Explore products that are available from this client store, backed by live inventory availability.',
                    'button_label' => 'Browse products',
                    'button_url' => '#products',
                    'image_path' => null,
                    'hero_stats' => [
                        ['value' => '4,200+', 'label' => 'Units Shipped'],
                        ['value' => '4.9&starf;', 'label' => 'Avg Rating'],
                        ['value' => '72 hr', 'label' => 'Avg Delivery'],
                    ],
                    'hero_marquee' => [
                        ['text' => 'CERTIFIED BUILD TECHNICIANS'],
                        ['text' => 'RTX 4090 IN STOCK'],
                        ['text' => '3-YEAR WARRANTY INCLUDED'],
                        ['text' => 'FREE SHIPPING OVER ₱50,000'],
                        ['text' => 'ZERO THERMAL THROTTLING'],
                        ['text' => '72-HOUR STRESS TESTED'],
                    ],
                ],
                [
                    'id' => 'tiers',
                    'enabled' => true,
                    'title' => "Select\nYour Tier",
                    'body' => 'Four configurations. Every one tested under load for 72 hours before it leaves our facility.',
                    'blocks' => [
                        ['listing_id' => ''],
                        ['listing_id' => ''],
                        ['listing_id' => ''],
                        ['listing_id' => '']
                    ]
                ],
                [
                    'id' => 'prebuilts',
                    'enabled' => true,
                    'title' => "Pre-Built\nSystems",
                    'body' => 'Ready to ship. Professionally assembled and stress-tested for out-of-the-box performance.',
                    'blocks' => [
                        ['listing_id' => ''],
                        ['listing_id' => ''],
                        ['listing_id' => ''],
                        ['listing_id' => '']
                    ]
                ],
                [
                    'id' => 'categories',
                    'enabled' => true,
                    'title' => "Explore\nCategories",
                    'body' => 'Find exactly what you need. From ready-to-ship systems to fully custom workstations.',
                ],
                [
                    'id' => 'cta',
                    'enabled' => true,
                    'title' => "Stop Settling.",
                    'subtitle' => "Start Winning.",
                    'body' => 'Free shipping. Free setup support. 30-day no-questions return policy. Your next machine is three clicks away.',
                    'primary_button_label' => 'Build Yours Now',
                    'primary_button_url' => '/configurator',
                    'secondary_button_label' => 'Talk To An Expert',
                    'secondary_button_url' => '/contact',
                    'tag_text' => 'READY_TO_BUILD',
                ],
            ],
        ];
    }

    public static function publishedFor(Company $company): array
    {
        if ($company->id && app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId() === null) {
            app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->setClientId((int) $company->id);
        }

        $layout = static::query()->where('client_id', $company->id)->first()
            ?: static::withoutGlobalScope('ecommerce-client')->whereNotNull('published_layout')->latest()->first()
            ?: static::withoutGlobalScope('ecommerce-client')->latest()->first();

        $data = $layout?->published_layout ?: $layout?->draft_layout ?: static::defaultFor($company);
        return static::mergeDefaultPages($data);
    }

    public static function editableFor(Company $company): array
    {
        if ($company->id && app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId() === null) {
            app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->setClientId((int) $company->id);
        }

        $layout = static::query()->where('client_id', $company->id)->first()
            ?: static::withoutGlobalScope('ecommerce-client')->whereNotNull('draft_layout')->latest()->first()
            ?: static::withoutGlobalScope('ecommerce-client')->latest()->first();

        $data = $layout?->draft_layout ?: $layout?->published_layout ?: static::defaultFor($company);
        return static::mergeDefaultPages($data);
    }

    private static function mergeDefaultPages(array $layout): array
    {
        $defaultPages = static::defaultFor(new Company())['custom_pages'];
        $existingPages = collect($layout['custom_pages'] ?? []);
        
        foreach ($defaultPages as $dp) {
            if (!$existingPages->contains('slug', $dp['slug'])) {
                $layout['custom_pages'][] = $dp;
            }
        }
        return $layout;
    }
}
