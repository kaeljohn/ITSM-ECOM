<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\AbandonedCart;
use Modules\Ecommerce\CRM\Models\Coupon;
use Modules\Ecommerce\CRM\Models\Lead;

class CrmDashboardService
{
    /**
     * Collect KPIs for the CRM dashboard.
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

        // Customers with no purchase in 90+ days (at-risk)
        $atRiskCount = Customer::where('last_purchase_at', '<', now()->subDays(90))
            ->orWhereNull('last_purchase_at')
            ->count();

        // Coupon stats
        $activeCoupons = Coupon::where('status', 'active')->count();
        $totalRedemptions = Coupon::sum('usage_count');

        // Sales pipeline stats
        $pipelineValue = Lead::inPipeline()->sum('expected_value');
        $wonCount = Lead::won()->count();
        $lostCount = Lead::lost()->count();
        $totalClosed = $wonCount + $lostCount;
        $winRate = $totalClosed > 0 ? round($wonCount / $totalClosed * 100) : 0;

        return compact(
            'totalCustomers',
            'totalSpent',
            'avgOrderValue',
            'repeatCount',
            'newThisMonth',
            'sources',
            'abandonedCount',
            'recoveredCount',
            'atRiskCount',
            'activeCoupons',
            'totalRedemptions',
            'pipelineValue',
            'wonCount',
            'lostCount',
            'winRate',
        );
    }
}
