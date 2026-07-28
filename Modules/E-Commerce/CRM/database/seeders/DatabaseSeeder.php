<?php

namespace Modules\Ecommerce\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    protected string $connection = 'ecommerce';
    protected ?int $clientId = 1;

    public function run(): void
    {
        $this->command?->info('🌱 Seeding CRM Module...');

        $now = Carbon::now();
        $clientId = $this->clientId;

        // ═══════════════════════════════════════════════════════════════
        // 1. TAGS
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Tags...');
        $tags = [
            ['client_id' => $clientId, 'name' => 'VIP',          'color' => '#7C3AED', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'New',          'color' => '#3B82F6', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Returning',    'color' => '#22C55E', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'At Risk',      'color' => '#EF4444', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'High Spender', 'color' => '#F59E0B', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Wholesale',    'color' => '#06B6D4', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Blog Reader',  'color' => '#EC4899', 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'B2B Lead',     'color' => '#F97316', 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::connection($this->connection)->table('crm_tags')->insert($tags);
        $tagIds = DB::connection($this->connection)->table('crm_tags')->pluck('id')->toArray();

        // ═══════════════════════════════════════════════════════════════
        // 2. SEGMENTS (11 RFM auto-segments + 2 manual)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Segments...');
        $segments = [
            // RFM Auto-Segments
            ['client_id' => $clientId, 'name' => 'Champions',          'slug' => 'champions',       'description' => 'Best customers — buy often, spend big, recent',              'is_auto' => true,  'criteria' => json_encode(['rfm' => [4,5,4,5,4,5]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Loyal Customers',    'slug' => 'loyal',           'description' => 'Regular buyers — moderate recency, high frequency',         'is_auto' => true,  'criteria' => json_encode(['rfm' => [2,5,3,5,3,5]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Potential Loyalists','slug' => 'potential',       'description' => 'Recent buyers, low frequency — need nurturing',              'is_auto' => true,  'criteria' => json_encode(['rfm' => [3,5,1,3,1,3]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'New Customers',      'slug' => 'recent',          'description' => 'Bought once recently — first purchase',                      'is_auto' => true,  'criteria' => json_encode(['rfm' => [4,5,1,1,1,3]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Promising',          'slug' => 'promising',       'description' => 'Recent but low spend — show potential',                      'is_auto' => true,  'criteria' => json_encode(['rfm' => [3,4,1,1,1,2]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Needs Attention',    'slug' => 'needs_attention', 'description' => 'Below average recency, frequency, monetary',                  'is_auto' => true,  'criteria' => json_encode(['rfm' => [2,3,1,2,1,2]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'About to Sleep',     'slug' => 'about_to_sleep',  'description' => 'Low recency and frequency — about to churn',                  'is_auto' => true,  'criteria' => json_encode(['rfm' => [1,2,1,2,1,2]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'At Risk',            'slug' => 'at_risk',         'description' => 'Used to be big spenders but gone cold',                       'is_auto' => true,  'criteria' => json_encode(['rfm' => [1,2,2,5,2,5]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Can\'t Lose Them',   'slug' => 'cant_lose',       'description' => 'Once-high-value customers who stopped buying',                'is_auto' => true,  'criteria' => json_encode(['rfm' => [1,2,4,5,4,5]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Hibernating',        'slug' => 'hibernating',     'description' => 'Low recency, low frequency — may return',                     'is_auto' => true,  'criteria' => json_encode(['rfm' => [1,2,1,2,1,5]]),           'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Lost',               'slug' => 'lost',            'description' => 'No purchases in a very long time — unlikely to return',        'is_auto' => true,  'criteria' => json_encode(['rfm' => [1,1,1,1,1,5]]),           'created_at' => $now, 'updated_at' => $now],
            // Manual segments
            ['client_id' => $clientId, 'name' => 'Newsletter Subscribers','slug' => 'newsletter',    'description' => 'Customers subscribed to email newsletter',                   'is_auto' => false, 'criteria' => json_encode(['sources' => ['direct', 'social']]), 'created_at' => $now, 'updated_at' => $now],
            ['client_id' => $clientId, 'name' => 'Promo Eligible',     'slug' => 'promo-eligible', 'description' => 'Customers eligible for promotional campaigns',               'is_auto' => false, 'criteria' => json_encode(['min_spent' => 1000]),            'created_at' => $now, 'updated_at' => $now],
        ];
        DB::connection($this->connection)->table('crm_segments')->insert($segments);
        $segmentIds = DB::connection($this->connection)->table('crm_segments')->pluck('id', 'slug')->toArray();

        // ═══════════════════════════════════════════════════════════════
        // 3. CUSTOMERS (25 with varied profiles)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Customers...');
        $firstNames = ['Juan','Maria','Jose','Ana','Pedro','Luisa','Miguel','Sofia','Carlos','Rosa',
                       'Andres','Elena','Ramon','Luz','Antonio','Clara','Fernando','Teresa','Ricardo','Gloria',
                       'Emilio','Carmen','Rafael','Consuelo','Manuel'];
        $lastNames  = ['dela Cruz','Santos','Reyes','Gonzalez','Ramos','Garcia','Mendoza','Flores','Aquino','Castillo',
                       'Cruz','Villanueva','Navarro','Rivera','Domingo','Mercado','Lazaro','Santiago','David','Ocampo',
                       'Bautista','Fernandez','Lopez','Martinez','Torres'];
        $sources    = ['direct','social','referral','lead','organic'];

        $customers = [];
        for ($i = 0; $i < 25; $i++) {
            $totalSpent = [0, 1500, 5000, 15000, 45000, 80000, 120000, 250000, 5000, 800,
                           25000, 60000, 3500, 95000, 180000, 2000, 40000, 7500, 150000, 30000,
                           10000, 55000, 12000, 200000, 0][$i];
            $orderCount = [0, 1, 3, 5, 8, 12, 15, 22, 4, 1, 7, 10, 2, 14, 18, 1, 9, 6, 20, 8, 4, 11, 5, 25, 0][$i];
            $daysSinceLastPurchase = [null, 15, 45, 10, 90, 30, 5, 2, 180, 365,
                                      60, 20, 120, 3, 1, 200, 40, 150, 7, 80,
                                      50, 25, 100, 4, null][$i];
            $lastPurchaseAt = $daysSinceLastPurchase !== null
                ? $now->copy()->subDays($daysSinceLastPurchase)
                : null;
            $aov = $orderCount > 0 ? round($totalSpent / $orderCount, 2) : 0;

            $customers[] = [
                'client_id'          => $clientId,
                'email'              => strtolower($firstNames[$i] . '.' . $lastNames[$i]) . '@example.com',
                'first_name'         => $firstNames[$i],
                'last_name'          => $lastNames[$i],
                'phone'              => '+639' . rand(100000000, 999999999),
                'source'             => $sources[$i % 5],
                'total_spent'        => $totalSpent,
                'order_count'        => $orderCount,
                'last_purchase_at'   => $lastPurchaseAt,
                'average_order_value'=> $aov,
                'engagement_score'   => round(match (true) {
                    $totalSpent > 100000 => rand(40, 50) / 10,
                    $totalSpent > 50000  => rand(30, 45) / 10,
                    $totalSpent > 10000  => rand(20, 35) / 10,
                    $totalSpent > 0      => rand(10, 25) / 10,
                    default              => rand(5, 15) / 10,
                }, 1),
                'churn_risk'         => match (true) {
                    $daysSinceLastPurchase === null => 'high',
                    $daysSinceLastPurchase > 180    => 'high',
                    $daysSinceLastPurchase > 90     => 'medium',
                    $daysSinceLastPurchase > 30     => 'low',
                    default                          => 'low',
                },
                'opt_in_email'       => (bool) rand(0, 1),
                'opt_in_sms'         => (bool) rand(0, 1),
                'opted_in_at'        => (bool) rand(0, 1) ? $now->copy()->subDays(rand(10, 200)) : null,
                'last_engaged_at'    => $lastPurchaseAt ?? $now->copy()->subDays(rand(1, 30)),
                'forge_points'       => rand(0, 500),
                'tier'               => match (true) {
                    $totalSpent > 150000 => 'platinum',
                    $totalSpent > 60000  => 'gold',
                    $totalSpent > 15000  => 'silver',
                    $totalSpent > 0      => 'bronze',
                    default              => 'unrated',
                },
                'notes'              => $i % 5 === 0 ? 'Internal note about ' . $firstNames[$i] : null,
                'created_at'         => $now->copy()->subDays(rand(30, 365)),
                'updated_at'         => $now,
            ];
        }
        // Insert in chunks to avoid parameter limit
        foreach (array_chunk($customers, 25) as $chunk) {
            DB::connection($this->connection)->table('crm_customers')->insert($chunk);
        }
        $customerIds = DB::connection($this->connection)->table('crm_customers')->pluck('id')->toArray();

        // ═══════════════════════════════════════════════════════════════
        // 4. CUSTOMER-TAG ASSIGNMENTS (tag 1-3 per customer)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Customer-Tag assignments...');
        $custTags = [];
        foreach ($customerIds as $cid) {
            $numTags = rand(1, 3);
            $assigned = (array) array_rand(array_flip($tagIds), min($numTags, count($tagIds)));
            foreach ($assigned as $tid) {
                $custTags[] = [
                    'client_id'    => $clientId,
                    'customer_id'  => $cid,
                    'tag_id'       => $tid,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        DB::connection($this->connection)->table('crm_customer_tags')->insert($custTags);

        // ═══════════════════════════════════════════════════════════════
        // 5. CUSTOMER-SEGMENT ASSIGNMENTS (1-2 segments per customer)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Customer-Segment assignments...');
        $manualSegmentIds = [
            $segmentIds['newsletter'],
            $segmentIds['promo-eligible'],
        ];
        $custSegs = [];
        foreach ($customerIds as $cid) {
            // Assign 1-2 manual segments
            $numSegs = rand(1, 2);
            $assignedSegs = (array) array_rand(array_flip($manualSegmentIds), min($numSegs, count($manualSegmentIds)));
            foreach ($assignedSegs as $sid) {
                $custSegs[] = [
                    'client_id'    => $clientId,
                    'customer_id'  => $cid,
                    'segment_id'   => $sid,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        if (!empty($custSegs)) {
            DB::connection($this->connection)->table('crm_customer_segments')->insert($custSegs);
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. TICKETS (15 tickets across customers)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Tickets...');
        $ticketSubjects = [
            'Order #12345 not yet delivered',
            'Wrong item received in my order',
            'Need help with PC build configurator',
            'Payment was charged twice',
            'How to track my shipment?',
            'Requesting refund for defective GPU',
            'Warranty claim for motherboard',
            'Billing inquiry for recent order',
            'Change shipping address for pending order',
            'Product recommendation for gaming PC',
            'Cancellation request for order #67890',
            'Missing parts in my PC kit',
            'Compatibility question for RAM upgrade',
            'Discount code not working',
            'Account login issue after password reset',
        ];
        $statuses = ['open', 'open', 'pending', 'pending', 'resolved', 'resolved', 'closed'];
        $priorities = ['low', 'normal', 'normal', 'high', 'high', 'urgent'];
        $categories = ['shipping', 'product', 'billing', 'account', 'other'];
        $channels = ['email', 'portal', 'chat', 'phone'];
        $assignedStaff = ['Ana Reyes', 'Carlos Mendoza', 'Maria Santos', null, null];
        $ticketIds = [];
        foreach (array_slice($ticketSubjects, 0, 15) as $idx => $subject) {
            $created = $now->copy()->subHours(rand(1, 720));
            $status = $statuses[$idx % count($statuses)];
            $ticketData = [
                'client_id'           => $clientId,
                'customer_id'         => $customerIds[$idx % count($customerIds)],
                'subject'             => $subject,
                'description'         => 'Detailed description for: ' . $subject,
                'status'              => $status,
                'priority'            => $priorities[$idx % count($priorities)],
                'channel'             => $channels[$idx % count($channels)],
                'assigned_to'         => $assignedStaff[$idx % count($assignedStaff)],
                'assigned_to_user_id' => $assignedStaff[$idx % count($assignedStaff)] ? rand(1, 3) : null,
                'category'            => $categories[$idx % count($categories)],
                'resolved_at'         => $status === 'resolved' ? $created->copy()->addDays(rand(1, 5)) : null,
                'closed_at'           => $status === 'closed' ? $created->copy()->addDays(rand(3, 10)) : null,
                'created_at'          => $created,
                'updated_at'          => $created->copy()->addHours(rand(1, 48)),
            ];
            $ticketIds[] = DB::connection($this->connection)->table('crm_tickets')->insertGetId($ticketData);
        }

        // ═══════════════════════════════════════════════════════════════
        // 7. TICKET NOTES (2-3 per ticket)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Ticket Notes...');
        $noteBodies = [
            'Checking with logistics team on this.',
            'Customer was notified via email about the resolution.',
            'Escalating to tier 2 support.',
            'Issue resolved — customer confirmed.',
            'Awaiting customer response.',
            'Refund has been processed.',
            'Replacing the unit under warranty.',
            'Contacted the customer but no reply yet.',
            'Parts are on backorder — ETA 2 weeks.',
            'Scheduled a callback for tomorrow.',
        ];
        $ticketNotes = [];
        foreach ($ticketIds as $ticketId) {
            $numNotes = rand(2, 3);
            for ($n = 0; $n < $numNotes; $n++) {
                $ticketNotes[] = [
                    'client_id'   => $clientId,
                    'ticket_id'   => $ticketId,
                    'author_id'   => rand(1, 3),
                    'author_name' => ['Ana Reyes', 'Carlos Mendoza', 'Maria Santos'][rand(0, 2)],
                    'body'        => $noteBodies[rand(0, count($noteBodies) - 1)],
                    'is_internal' => (bool) rand(0, 1),
                    'created_at'  => $now->copy()->subHours(rand(1, 48)),
                    'updated_at'  => $now,
                ];
            }
        }
        DB::connection($this->connection)->table('crm_ticket_notes')->insert($ticketNotes);

        // ═══════════════════════════════════════════════════════════════
        // 8. CAMPAIGN LOGS (10 campaigns across customers)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Campaign Logs...');
        $campaignNames = ['July Newsletter', 'Welcome Series #1', 'Flash Sale Alert', 'Abandoned Cart Reminder', 'Birthday Promo',
                          'Re-engagement Campaign', 'New Arrivals Announcement', 'VIP Exclusive Offer', 'Holiday Sale', 'Review Request'];
        $campaignStatuses = ['delivered', 'delivered', 'opened', 'clicked', 'bounced', 'sent', 'queued', 'delivered', 'opened', 'delivered'];
        $campaignLogIds = [];
        foreach ($campaignNames as $idx => $name) {
            $created = $now->copy()->subDays(rand(1, 60));
            $sentAt = $created->copy()->addMinutes(rand(5, 60));
            $isDelivered = in_array($campaignStatuses[$idx], ['delivered', 'opened', 'clicked']);
            $isOpened = in_array($campaignStatuses[$idx], ['opened', 'clicked']);
            $isClicked = $campaignStatuses[$idx] === 'clicked';
            $logId = DB::connection($this->connection)->table('crm_campaign_log')->insertGetId([
                'client_id'          => $clientId,
                'customer_id'        => $customerIds[$idx % count($customerIds)],
                'campaign_name'      => $name,
                'campaign_type'      => $idx % 3 === 2 ? 'sms' : 'email',
                'subject'            => $name === 'Welcome Series #1' ? 'Welcome to Nexora! 🎉' : ($name === 'Flash Sale Alert' ? '⚡ 24-Hour Flash Sale!' : $name),
                'body_preview'       => 'Preview of ' . $name . ' campaign content...',
                'direction'          => 'outbound',
                'status'             => $campaignStatuses[$idx],
                'template_id'        => null,
                'sent_by_user_id'    => rand(1, 3),
                'provider'           => $idx % 2 === 0 ? 'ses' : 'sendgrid',
                'sent_at'            => $sentAt,
                'delivered_at'       => $isDelivered ? $sentAt->copy()->addMinutes(rand(1, 10)) : null,
                'first_opened_at'    => $isOpened ? $sentAt->copy()->addHours(rand(1, 48)) : null,
                'first_clicked_at'   => $isClicked ? $sentAt->copy()->addHours(rand(2, 72)) : null,
                'created_at'         => $created,
                'updated_at'         => $now,
            ]);
            $campaignLogIds[] = $logId;
        }

        // ═══════════════════════════════════════════════════════════════
        // 9. CAMPAIGN EVENTS (2-4 per campaign log)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Campaign Events...');
        $campaignEvents = [];
        $countries = ['Philippines', 'United States', 'Singapore', 'Japan', 'Australia'];
        $cities = ['Manila', 'Quezon City', 'Makati', 'Cebu', 'Davao'];
        $deviceTypes = ['desktop', 'mobile', 'tablet'];
        foreach ($campaignLogIds as $logId) {
            $status = DB::connection($this->connection)->table('crm_campaign_log')->where('id', $logId)->value('status');
            $sentAt = DB::connection($this->connection)->table('crm_campaign_log')->where('id', $logId)->value('sent_at');

            // Delivered event
            if (in_array($status, ['delivered', 'opened', 'clicked'])) {
                $campaignEvents[] = [
                    'client_id'       => $clientId,
                    'campaign_log_id' => $logId,
                    'event_type'      => 'delivered',
                    'payload'         => null,
                    'device_type'     => $deviceTypes[rand(0, 2)],
                    'country'         => $countries[rand(0, 4)],
                    'city'            => $cities[rand(0, 4)],
                    'ip_address'      => rand(10, 200) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                    'occurred_at'     => Carbon::parse($sentAt)->addMinutes(rand(1, 10)),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            // Open event
            if (in_array($status, ['opened', 'clicked'])) {
                $campaignEvents[] = [
                    'client_id'       => $clientId,
                    'campaign_log_id' => $logId,
                    'event_type'      => 'opened',
                    'payload'         => null,
                    'device_type'     => $deviceTypes[rand(0, 2)],
                    'country'         => $countries[rand(0, 4)],
                    'city'            => $cities[rand(0, 4)],
                    'ip_address'      => rand(10, 200) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                    'occurred_at'     => Carbon::parse($sentAt)->addHours(rand(1, 48)),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            // Click event
            if ($status === 'clicked') {
                $campaignEvents[] = [
                    'client_id'       => $clientId,
                    'campaign_log_id' => $logId,
                    'event_type'      => 'clicked',
                    'payload'         => 'https://nexora.com/promo/' . rand(100, 999),
                    'device_type'     => $deviceTypes[rand(0, 2)],
                    'country'         => $countries[rand(0, 4)],
                    'city'            => $cities[rand(0, 4)],
                    'ip_address'      => rand(10, 200) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                    'occurred_at'     => Carbon::parse($sentAt)->addHours(rand(2, 72)),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }
        if (!empty($campaignEvents)) {
            DB::connection($this->connection)->table('crm_campaign_events')->insert($campaignEvents);
        }

        // ═══════════════════════════════════════════════════════════════
        // 10. ACTIVITY LOG (40+ events across customers)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Activity Log...');
        $activityTypes = ['order', 'ticket', 'campaign', 'note', 'review', 'system'];
        $activityActions = ['created', 'updated', 'resolved', 'sent', 'opened', 'completed', 'flagged'];
        $activityLogs = [];
        foreach (array_slice($customerIds, 0, 15) as $cid) {
            $numActivities = rand(2, 4);
            for ($a = 0; $a < $numActivities; $a++) {
                $type = $activityTypes[rand(0, count($activityTypes) - 1)];
                $action = $activityActions[rand(0, count($activityActions) - 1)];
                $activityLogs[] = [
                    'client_id'    => $clientId,
                    'customer_id'  => $cid,
                    'type'         => $type,
                    'action'       => $action,
                    'summary'      => ucfirst($type) . ' ' . $action . ' — sample event',
                    'occurred_at'  => $now->copy()->subHours(rand(1, 720)),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        DB::connection($this->connection)->table('crm_activity_log')->insert($activityLogs);

        // ═══════════════════════════════════════════════════════════════
        // 11. LEADS (8 leads in various pipeline stages)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Leads...');
        $leadData = [
            ['first_name' => 'Antonio', 'last_name' => 'Lopez',    'email' => 'antonio.lopez@corp.com',  'company_name' => 'Lopez Industries',  'status' => 'new',         'source' => 'referral', 'expected_value' => 50000,  'probability' => 20],
            ['first_name' => 'Sofia',   'last_name' => 'Martinez', 'email' => 'sofia.m@techcorp.com',     'company_name' => 'TechCorp PH',       'status' => 'contacted',   'source' => 'website',  'expected_value' => 120000, 'probability' => 30],
            ['first_name' => 'Ramon',   'last_name' => 'Garcia',   'email' => 'ramon.g@builders.com',    'company_name' => 'Builders Inc.',     'status' => 'qualified',   'source' => 'referral', 'expected_value' => 250000, 'probability' => 50],
            ['first_name' => 'Clara',   'last_name' => 'Lim',      'email' => 'clara.lim@startup.ph',    'company_name' => 'Startup PH',        'status' => 'proposal',    'source' => 'cold_call','expected_value' => 80000,  'probability' => 60],
            ['first_name' => 'Jose',    'last_name' => 'Reyes',     'email' => 'jose.reyes@acme.com',     'company_name' => 'Acme Trading',      'status' => 'negotiation', 'source' => 'website',  'expected_value' => 350000, 'probability' => 75],
            ['first_name' => 'Luisa',   'last_name' => 'Tan',       'email' => 'luisa.tan@global.ph',    'company_name' => 'Global Solutions',  'status' => 'won',         'source' => 'referral', 'expected_value' => 180000, 'probability' => 100],
            ['first_name' => 'Pedro',   'last_name' => 'Villar',    'email' => 'pedro.v@oldcorp.com',     'company_name' => 'Old Corp',          'status' => 'lost',        'source' => 'cold_call','expected_value' => 60000,  'probability' => 0],
            ['first_name' => 'Maria',   'last_name' => 'Domingo',   'email' => 'maria.d@partners.com',    'company_name' => 'Partners Co.',      'status' => 'contacted',   'source' => 'event',    'expected_value' => 95000,  'probability' => 25],
        ];
        $leadIds = [];
        foreach ($leadData as $idx => $ld) {
            $created = $now->copy()->subDays(rand(5, 60));
            $leadIds[] = DB::connection($this->connection)->table('crm_leads')->insertGetId([
                'client_id'          => $clientId,
                'first_name'         => $ld['first_name'],
                'last_name'          => $ld['last_name'],
                'email'              => $ld['email'],
                'phone'              => '+639' . rand(100000000, 999999999),
                'company_name'       => $ld['company_name'],
                'status'             => $ld['status'],
                'source'             => $ld['source'],
                'expected_value'     => $ld['expected_value'],
                'actual_value'       => $ld['status'] === 'won' ? $ld['expected_value'] : 0,
                'probability'        => $ld['probability'],
                'expected_close_date'=> $ld['status'] === 'won' ? $created->copy()->addDays(rand(5, 30)) : $now->copy()->addDays(rand(10, 60)),
                'assigned_to'        => ['Ana Reyes', 'Carlos Mendoza', 'Maria Santos'][rand(0, 2)],
                'customer_id'        => $idx < 4 ? $customerIds[$idx] : null,
                'notes'              => 'Notes for ' . $ld['first_name'] . ' ' . $ld['last_name'],
                'activity_log'       => json_encode([['action' => 'created', 'description' => 'Lead created', 'timestamp' => $created->toIso8601String()]]),
                'created_at'         => $created,
                'updated_at'         => $now,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 12. COUPONS (5 coupons)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Coupons...');
        $couponIds = [];
        $couponData = [
            ['code' => 'WELCOME10',  'type' => 'percentage',   'value' => 10,  'description' => '10% off for new customers',                     'max_uses' => 100,  'usage_count' => 23],
            ['code' => 'FREESHIP',   'type' => 'free_shipping','value' => 0,   'description' => 'Free shipping on orders over ₱1,000',           'max_uses' => 200,  'usage_count' => 45],
            ['code' => 'VIP500',     'type' => 'fixed',        'value' => 500, 'description' => '₱500 off for VIP members',                       'max_uses' => 50,   'usage_count' => 12],
            ['code' => 'FLASH20',    'type' => 'percentage',   'value' => 20,  'description' => '20% off flash sale — limited time!',              'max_uses' => 30,   'usage_count' => 28],
            ['code' => 'LOYALTY5',   'type' => 'percentage',   'value' => 5,   'description' => '5% back for loyal customers',                     'max_uses' => null, 'usage_count' => 8],
        ];
        foreach ($couponData as $cd) {
            $couponIds[] = DB::connection($this->connection)->table('crm_coupons')->insertGetId([
                'client_id'       => $clientId,
                'code'            => $cd['code'],
                'type'            => $cd['type'],
                'value'           => $cd['value'],
                'max_uses'        => $cd['max_uses'],
                'usage_count'     => $cd['usage_count'],
                'status'          => 'active',
                'starts_at'       => $now->copy()->subDays(30),
                'expires_at'      => $now->copy()->addDays(60),
                'description'     => $cd['description'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 13. COUPON REDEMPTIONS (10 redemptions)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Coupon Redemptions...');
        if (!empty($couponIds)) {
            $redemptions = [];
            for ($r = 0; $r < 10; $r++) {
                $redemptions[] = [
                    'client_id'       => $clientId,
                    'coupon_id'       => $couponIds[rand(0, count($couponIds) - 1)],
                    'discount_amount' => rand(50, 500),
                    'redeemed_at'     => $now->copy()->subDays(rand(1, 20)),
                ];
            }
            DB::connection($this->connection)->table('crm_coupon_redemptions')->insert($redemptions);
        }

        // ═══════════════════════════════════════════════════════════════
        // 14. COMMUNICATIONS (10 records across customers)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Communications...');
        $commTypes = ['email', 'email', 'sms'];
        $commStatuses = ['sent', 'delivered', 'failed'];
        $comms = [];
        foreach (array_slice($customerIds, 0, 10) as $idx => $cid) {
            $comms[] = [
                'client_id'   => $clientId,
                'customer_id' => $cid,
                'type'        => $commTypes[$idx % count($commTypes)],
                'subject'     => 'Communication #' . ($idx + 1) . ' — sample',
                'body'        => 'Sample communication body for customer #' . $cid,
                'direction'   => 'outbound',
                'status'      => $commStatuses[$idx % count($commStatuses)],
                'sent_at'     => $now->copy()->subDays(rand(1, 30)),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        DB::connection($this->connection)->table('crm_communications')->insert($comms);

        // ═══════════════════════════════════════════════════════════════
        // 15. CONSENT LOGS (15 entries)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Consent Log...');
        $consentSources = ['registration', 'profile_update', 'checkout', 'manual'];
        $consentChannels = ['email', 'sms'];
        $consentLogs = [];
        foreach (array_slice($customerIds, 0, 8) as $cid) {
            $consentLogs[] = [
                'client_id'        => $clientId,
                'customer_id'      => $cid,
                'channel'          => 'email',
                'action'           => 'opt_in',
                'source'           => $consentSources[rand(0, 3)],
                'ip_address'       => rand(10, 200) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                'occurred_at'      => $now->copy()->subDays(rand(10, 90)),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $consentLogs[] = [
                'client_id'        => $clientId,
                'customer_id'      => $cid,
                'channel'          => 'sms',
                'action'           => rand(0, 1) ? 'opt_in' : 'opt_out',
                'source'           => $consentSources[rand(0, 3)],
                'ip_address'       => rand(10, 200) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                'occurred_at'      => $now->copy()->subDays(rand(5, 60)),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }
        DB::connection($this->connection)->table('crm_consent_log')->insert($consentLogs);

        // ═══════════════════════════════════════════════════════════════
        // 16. ABANDONED CARTS (5 carts)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Abandoned Carts...');
        $cartStatuses = ['pending', 'pending', 'pending', 'recovered', 'expired'];
        $cartItems = [
            [['name' => 'RTX 4060 GPU', 'price' => 18500, 'qty' => 1], ['name' => '16GB DDR5 RAM', 'price' => 3500, 'qty' => 2]],
            [['name' => 'Mechanical Keyboard', 'price' => 4500, 'qty' => 1]],
            [['name' => 'Gaming Mouse', 'price' => 2500, 'qty' => 1], ['name' => 'Mouse Pad XL', 'price' => 800, 'qty' => 1], ['name' => 'USB Hub', 'price' => 1200, 'qty' => 1]],
            [['name' => '27" Monitor', 'price' => 15500, 'qty' => 1]],
            [['name' => 'Webcam HD', 'price' => 3200, 'qty' => 1], ['name' => 'Microphone', 'price' => 4500, 'qty' => 1]],
        ];
        foreach (array_slice($customerIds, 0, 5) as $idx => $cid) {
            $total = collect($cartItems[$idx])->sum(fn($i) => $i['price'] * $i['qty']);
            DB::connection($this->connection)->table('crm_abandoned_carts')->insert([
                'client_id'      => $clientId,
                'user_id'        => $cid,
                'email'          => 'customer' . $cid . '@example.com',
                'cart_total'     => $total,
                'items_summary'  => json_encode($cartItems[$idx]),
                'status'         => $cartStatuses[$idx],
                'abandoned_at'   => $now->copy()->subHours(rand(2, 48)),
                'recovered_at'   => $cartStatuses[$idx] === 'recovered' ? $now->copy()->subHours(rand(1, 24)) : null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 17. PRODUCT REVIEWS (8 reviews)
        // ═══════════════════════════════════════════════════════════════
        $this->command?->info('  → Product Reviews...');
        $reviewTexts = [
            'Great product! Highly recommend.',
            'Good quality for the price.',
            'Works as expected. Fast shipping.',
            'Average. Not bad but not great.',
            'Excellent build quality and performance.',
            'Decent product but packaging could be better.',
            'Outstanding! Will buy again.',
            'Not what I expected. A bit disappointed.',
        ];
        $reviewTitles = ['Love it!', 'Good buy', 'Okay', 'Solid', 'Excellent!', 'Decent', 'Amazing!', 'Meh'];
        $ratings = [5, 4, 4, 3, 5, 3, 5, 2];
        foreach (array_slice($customerIds, 0, 8) as $idx => $cid) {
            DB::connection($this->connection)->table('crm_product_reviews')->insert([
                'client_id'   => $clientId,
                'user_id'     => $cid,
                'listing_id'  => rand(1, 50),
                'rating'      => $ratings[$idx],
                'title'       => $reviewTitles[$idx],
                'body'        => $reviewTexts[$idx],
                'approved'    => (bool) rand(0, 1),
                'approved_at' => (bool) rand(0, 1) ? $now->copy()->subDays(rand(1, 10)) : null,
                'created_at'  => $now->copy()->subDays(rand(5, 60)),
                'updated_at'  => $now,
            ]);
        }

        $this->command?->info('✅ CRM seeding complete!');
    }
}
