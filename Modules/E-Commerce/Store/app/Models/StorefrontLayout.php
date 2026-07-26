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
                ['id' => 'collections', 'slug' => 'collections', 'title' => 'Collections', 'blueprint' => 'collections'],
                ['id' => 'prebuilt-pcs', 'slug' => 'prebuilt-pcs', 'title' => 'Prebuilt PCs', 'blueprint' => 'prebuilt-pcs'],
                ['id' => 'pc-configurator', 'slug' => 'pc-configurator', 'title' => 'PC Configurator', 'blueprint' => 'pc-configurator'],
                ['id' => 'build-pc', 'slug' => 'build-pc', 'title' => 'Build PC', 'blueprint' => 'build-pc'],
            ],
            'support_pages' => [
                'contact' => [
                    'title' => 'Contact & FAQ',
                    'subtitle' => "We'd love to hear from you",
                    'cards' => [
                        ['icon' => 'envelope', 'title' => 'Email', 'detail' => 'support@store.com', 'sub' => 'We respond within 24 hours'],
                        ['icon' => 'phone', 'title' => 'Phone', 'detail' => '+63 (2) 8123 4567', 'sub' => 'Mon-Sat, 9:00 AM - 6:00 PM'],
                        ['icon' => 'map-pin', 'title' => 'Location', 'detail' => 'Metro Manila, Philippines', 'sub' => 'By appointment only'],
                        ['icon' => 'chat-circle-text', 'title' => 'Live Chat', 'detail' => 'Chat with us in real-time', 'sub' => 'Available during business hours'],
                    ],
                    'faq_title' => 'Frequently Asked Questions',
                    'faq_items' => [
                        ['q' => 'What payment methods do you accept?', 'a' => 'We accept credit/debit cards (Visa, Mastercard, Amex), GCash, Maya, bank transfers, and cash on delivery (COD) for select areas.'],
                        ['q' => 'How long does shipping take?', 'a' => 'Metro Manila orders typically arrive within 1-3 business days. Provincial orders may take 3-7 business days depending on location. Custom orders may require additional processing time.'],
                        ['q' => 'What is your warranty policy?', 'a' => 'All products come with a warranty covering manufacturer defects. Warranty terms vary by product category. Please see our Returns page for full details.'],
                        ['q' => 'Can I return or exchange a product?', 'a' => 'Yes! You can return unopened items within 30 days of delivery for a full refund. Certain items may have specific return conditions.'],
                        ['q' => 'Do you offer installment plans?', 'a' => 'Yes, we offer 0% interest installment plans through select credit cards and financing partners. Terms vary by provider.'],
                        ['q' => 'How do I track my order?', 'a' => 'Once your order ships, you will receive an email with a tracking number and courier link. You can also view your order status by logging into your account and visiting Order History.'],
                        ['q' => 'How can I contact your support team?', 'a' => 'Our support team is available Monday to Saturday, 9 AM to 6 PM. You can reach us via live chat, email, or phone.'],
                        ['q' => 'Do you offer bulk or wholesale pricing?', 'a' => 'Yes, we offer special pricing for bulk and wholesale orders. Please contact our sales team for a personalized quote.'],
                    ],
                ],
                'shipping' => [
                    'title' => 'Shipping & Delivery',
                    'subtitle' => 'Everything you need to know about shipping',
                    'rates' => [
                        ['label' => 'Metro Manila', 'desc' => 'Standard delivery within 1-3 business days', 'price' => '₱150', 'highlighted' => false],
                        ['label' => 'Provincial (Luzon)', 'desc' => 'Delivery within 3-5 business days', 'price' => '₱250', 'highlighted' => false],
                        ['label' => 'Provincial (Visayas / Mindanao)', 'desc' => 'Delivery within 5-7 business days', 'price' => '₱350', 'highlighted' => false],
                        ['label' => 'Free Shipping', 'desc' => 'On qualifying orders', 'price' => 'FREE', 'highlighted' => true],
                    ],
                    'processing' => [
                        ['label' => '1-2 Days', 'desc' => 'In-stock items'],
                        ['label' => '3-5 Days', 'desc' => 'Made-to-order or customized items'],
                    ],
                    'tracking_body' => 'Once your order ships, you will receive a confirmation email with a tracking number. You can also track your order directly from your account dashboard.',
                ],
                'returns' => [
                    'title' => 'Returns & Exchanges',
                    'subtitle' => 'Your satisfaction is our priority',
                    'warranty_title' => 'Warranty Coverage',
                    'warranty_sub' => 'Coverage terms vary by product',
                    'warranty_body' => 'All products are covered by a warranty against manufacturer defects. Warranty duration and terms vary by product category. Please refer to your product documentation or contact our support team for specific warranty information for your item.',
                    'policy' => [
                        ['icon' => 'check', 'color' => 'green', 'title' => 'Unopened Items', 'desc' => 'Full refund within 30 days of delivery. Item must be in original packaging with all accessories.'],
                        ['icon' => 'warning', 'color' => 'yellow', 'title' => 'Opened / Used Items', 'desc' => 'Subject to inspection and possible restocking fee. Must be returned within 14 days.'],
                        ['icon' => 'x', 'color' => 'red', 'title' => 'Custom / Personalized Items', 'desc' => 'Returns accepted within 7 days only for verified defects. Please contact support immediately if you receive a defective item.'],
                    ],
                    'process_title' => 'Return Process',
                    'process_sub' => 'How to initiate a return',
                    'steps' => [
                        ['num' => 1, 'title' => 'Contact Support', 'desc' => 'Reach out via email or live chat with your order number and reason for return.'],
                        ['num' => 2, 'title' => 'Get Return Authorization', 'desc' => 'Our team will review your request and issue a return authorization within 1-2 business days.'],
                        ['num' => 3, 'title' => 'Ship the Item', 'desc' => 'Package the item securely with all original accessories and ship to our returns center.'],
                        ['num' => 4, 'title' => 'Inspection & Refund', 'desc' => 'Once received, we inspect the item within 3-5 business days. Refunds are processed within 7-14 business days.'],
                    ],
                ],
            ],
            'company_pages' => [
                'about' => [
                    'title' => 'About Us',
                    'subtitle' => 'Learn more about our store',
                    'story' => "Welcome to our store! We are dedicated to bringing you the best products and an exceptional shopping experience. Our team carefully curates every item in our catalog to ensure it meets our high standards for quality, reliability, and value.\n\nWe believe in putting our customers first. From easy ordering to fast shipping and reliable support, every step of your journey with us is designed to exceed expectations.\n\nThank you for choosing us. We look forward to serving you!",
                    'values' => [
                        ['icon' => 'medal', 'title' => 'Quality', 'description' => 'Every product meets our strict quality standards before it reaches your door.'],
                        ['icon' => 'shield-check', 'title' => 'Trust', 'description' => 'We stand behind every purchase with reliable support and fair policies.'],
                        ['icon' => 'rocket', 'title' => 'Innovation', 'description' => 'We constantly evolve to bring you the best selection and service.'],
                    ],
                    'cta_label' => 'Browse Store',
                ],
                'careers' => [
                    'title' => 'Careers',
                    'subtitle' => 'Join our team',
                    'body' => 'We are always looking for talented individuals to join our growing team. Check back soon for open positions.',
                    'open_positions' => [],
                ],
                'affiliates' => [
                    'title' => 'Affiliates',
                    'subtitle' => 'Partner with us',
                    'body' => 'Join our affiliate program and earn commissions by promoting our products. Reach out to our partnerships team for more information.',
                    'benefits' => [],
                    'cta_label' => 'Apply Now',
                ],
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
