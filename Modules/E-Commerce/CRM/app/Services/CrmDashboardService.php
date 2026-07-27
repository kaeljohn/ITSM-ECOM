<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Ecommerce\CRM\Models\ActivityLog;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\AbandonedCart;
use Modules\Ecommerce\CRM\Models\CampaignLog;
use Modules\Ecommerce\CRM\Models\CommunicationTemplate;
use Modules\Ecommerce\CRM\Models\Coupon;
use Modules\Ecommerce\CRM\Models\Lead;
use Modules\Ecommerce\CRM\Models\ProductReview;
use Modules\Ecommerce\CRM\Models\Segment;
use Modules\Ecommerce\CRM\Models\Ticket;

class CrmDashboardService
{
    /**
     * Collect KPIs for the CRM dashboard — extended version.
     */
    public function overview(): array
    {
        $totalCustomers = Customer::count();
        $totalSpent = Customer::sum('total_spent');
        $avgOrderValue = Customer::avg('average_order_value') ?: 0;

        // Repeat customers: those with order_count > 1
        $repeatCount = Customer::where('order_count', '>', 1)->count();

        // New this month
        $newThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();

        // Top sources
        $sources = Customer::select('source', DB::raw('count(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Abandoned carts count
        $abandonedCount = AbandonedCart::where('status', 'pending')->count();
        $recoveredCount = AbandonedCart::where('status', 'recovered')->count();

        // At-risk: no purchase in 90+ days OR churn_risk = 'high'
        $atRiskCount = Customer::where(function ($q) {
            $q->where('churn_risk', 'high')
              ->orWhere(function ($q2) {
                  $q2->where('last_purchase_at', '<', now()->subDays(90))
                     ->orWhereNull('last_purchase_at');
              });
        })->count();

        // Churn risk distribution
        $churnDistribution = Customer::select('churn_risk', DB::raw('count(*) as count'))
            ->whereNotNull('churn_risk')
            ->groupBy('churn_risk')
            ->get()
            ->keyBy('churn_risk');

        $churnHigh   = (int) ($churnDistribution['high']->count ?? 0);
        $churnMedium = (int) ($churnDistribution['medium']->count ?? 0);
        $churnLow    = (int) ($churnDistribution['low']->count ?? 0);

        // Tier distribution
        $tierDistribution = Customer::select('tier', DB::raw('count(*) as count'))
            ->whereNotNull('tier')
            ->groupBy('tier')
            ->get()
            ->keyBy('tier');

        $tierBronze   = (int) ($tierDistribution['bronze']->count ?? 0);
        $tierSilver   = (int) ($tierDistribution['silver']->count ?? 0);
        $tierGold     = (int) ($tierDistribution['gold']->count ?? 0);
        $tierPlatinum = (int) ($tierDistribution['platinum']->count ?? 0);

        // Engagement score stats
        $avgEngagement = Customer::avg('engagement_score') ?: 0;

        // Customers with opted-in marketing
        $optInEmail = Customer::where('opt_in_email', true)->count();
        $optInSms   = Customer::where('opt_in_sms', true)->count();

        // Coupon stats
        $activeCoupons    = Coupon::where('status', 'active')->count();
        $totalRedemptions = Coupon::sum('usage_count');

        // Sales pipeline stats
        $pipelineValue = Lead::inPipeline()->sum('expected_value');
        $wonCount      = Lead::won()->count();
        $lostCount     = Lead::lost()->count();
        $totalClosed   = $wonCount + $lostCount;
        $winRate       = $totalClosed > 0 ? round($wonCount / $totalClosed * 100) : 0;

        // ── Tickets (active) ──
        $activeTickets   = Ticket::active()->count();
        $urgentTickets   = Ticket::active()->where('priority', 'urgent')->count();
        $unassignedTickets = Ticket::active()->whereNull('assigned_to_user_id')->count();
        $ticketByStatus  = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Recent tickets (last 10 active, newest first, with customer name)
        $recentTickets = Ticket::active()
            ->with(['customer' => function ($q) { $q->select('id', 'first_name', 'last_name', 'email'); }])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->toArray();

        // ── Top customers by total_spent ──
        $topCustomers = Customer::where('total_spent', '>', 0)
            ->orderByDesc('total_spent')
            ->take(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'total_spent', 'order_count', 'tier', 'churn_risk', 'last_purchase_at'])
            ->toArray();

        // ── Recent activity feed (last 15 entries from activity_log) ──
        $recentActivity = ActivityLog::with(['customer' => function ($q) {
            $q->select('id', 'first_name', 'last_name');
        }])
        ->orderByDesc('occurred_at')
        ->take(15)
        ->get()
        ->toArray();

        // ── Monthly signup trend (last 6 months) ──
        $monthlySignups = Customer::select(
            DB::raw("to_char(created_at, 'YYYY-MM') as month"),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy(DB::raw("to_char(created_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get()
            ->toArray();

        // ── New customers this week ──
        $newThisWeek = Customer::where('created_at', '>=', now()->startOfWeek())->count();

        // ── Customers by engagement level ──
        $highEngagement  = Customer::where('engagement_score', '>=', 4.0)->count();
        $mediumEngagement = Customer::whereBetween('engagement_score', [2.0, 3.99])->count();
        $lowEngagement   = Customer::where('engagement_score', '<', 2.0)->count();

        // ─── Campaign KPIs ────────────────────────────────────────────
        $totalCampaignsSent   = CampaignLog::count();
        $campaignsDelivered   = CampaignLog::delivered()->count();
        $campaignsOpened      = CampaignLog::opened()->count();
        $campaignsClicked     = CampaignLog::clicked()->count();
        $campaignsFailed      = CampaignLog::failed()->count();
        $campaignDeliveryRate = $totalCampaignsSent > 0 ? round($campaignsDelivered / $totalCampaignsSent * 100) : 0;

        // ─── Template KPIs ────────────────────────────────────────────
        $totalTemplates       = CommunicationTemplate::count();
        $activeTemplates      = CommunicationTemplate::active()->count();

        // ─── Segment KPIs ─────────────────────────────────────────────
        $totalSegments        = Segment::count();
        $autoSegments         = Segment::where('is_auto', true)->count();
        $totalCustomersSegmented = Schema::hasTable('crm_customer_segments')
            ? DB::table('crm_customer_segments')->distinct('customer_id')->count('customer_id')
            : 0;

        // ─── Pending Reviews (alerts) ─────────────────────────────────
        $pendingReviews = ProductReview::where('approved', false)->count();

        // ─── Unassigned Tickets (already computed above, just a named ref) ──
        // Already have $unassignedTickets

        return compact(
            // Basic stats
            'totalCustomers',
            'totalSpent',
            'avgOrderValue',
            'repeatCount',
            'newThisMonth',
            'newThisWeek',
            'sources',

            // Abandoned carts
            'abandonedCount',
            'recoveredCount',

            // Churn & Risk
            'atRiskCount',
            'churnHigh',
            'churnMedium',
            'churnLow',

            // Tiers
            'tierBronze',
            'tierSilver',
            'tierGold',
            'tierPlatinum',

            // Engagement
            'avgEngagement',
            'highEngagement',
            'mediumEngagement',
            'lowEngagement',
            'optInEmail',
            'optInSms',

            // Coupons
            'activeCoupons',
            'totalRedemptions',

            // Pipeline
            'pipelineValue',
            'wonCount',
            'lostCount',
            'winRate',

            // Tickets
            'activeTickets',
            'urgentTickets',
            'unassignedTickets',
            'ticketByStatus',
            'recentTickets',

            // Customers
            'topCustomers',

            // Activity
            'recentActivity',

            // Trends
            'monthlySignups',

            // Campaigns
            'totalCampaignsSent',
            'campaignsDelivered',
            'campaignsOpened',
            'campaignsClicked',
            'campaignsFailed',
            'campaignDeliveryRate',

            // Templates
            'totalTemplates',
            'activeTemplates',

            // Segments
            'totalSegments',
            'autoSegments',
            'totalCustomersSegmented',

            // Alerts
            'pendingReviews',
        );
    }
}
