<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\Models\Order;

class LtvCalculator
{
    /**
     * LTV tier boundaries (based on total_spent).
     */
    const TIER_THRESHOLDS = [
        'platinum' => 100000,
        'gold'     => 50000,
        'silver'   => 10000,
        'bronze'   => 0,
    ];

    const TIER_NAMES = ['platinum', 'gold', 'silver', 'bronze'];

    /**
     * Engagement weight coefficients (used in computeEngagement).
     */
    const ENGAGEMENT_WEIGHTS = [
        'order'         => 2.0,  // Placing an order
        'ticket'        => 1.0,  // Opening a support ticket
        'campaign_open' => 0.5,  // Opening an email
        'campaign_click'=> 0.8,  // Clicking a campaign link
        'review'        => 1.2,  // Writing a product review
        'login'         => 0.3,  // Logging in
    ];

    // ─── Single Customer ──────────────────────────────────────────────

    /**
     * Compute LTV-derived metrics for a single customer and persist them.
     */
    public function computeForCustomer(Customer $customer): Customer
    {
        $orders = $this->getCustomerOrders($customer);
        $orderCount = $orders->count();

        $totalSpent = $orders->sum('total');
        $averageOrderValue = $orderCount > 0
            ? round($totalSpent / $orderCount, 2)
            : 0.0;

        $lastPurchaseAt = $orderCount > 0
            ? $orders->max('created_at')
            : null;

        $tier = $this->determineTier($totalSpent);
        $engagementScore = $this->computeEngagement(
            $customer,
            $orderCount,
            $lastPurchaseAt
        );

        $customer->update([
            'total_spent'         => $totalSpent,
            'order_count'         => $orderCount,
            'average_order_value' => $averageOrderValue,
            'last_purchase_at'    => $lastPurchaseAt,
            'tier'                => $tier,
            'engagement_score'    => $engagementScore,
            'last_engaged_at'     => now(),
        ]);

        $customer->refresh();

        return $customer;
    }

    /**
     * Projected LTV for the next 12 months based on current velocity.
     */
    public function projectedLtv(Customer $customer): float
    {
        if ($customer->order_count === 0 || !$customer->created_at) {
            return 0.0;
        }

        $monthsActive = max(1, $customer->created_at->diffInMonths(now()));
        $monthlyAvgSpend = $customer->total_spent / $monthsActive;

        // Project forward 12 months
        return round($monthlyAvgSpend * 12, 2);
    }

    /**
     * Purchased months ago for recency analysis.
     */
    public function monthsSinceLastPurchase(Customer $customer): ?int
    {
        if (!$customer->last_purchase_at) {
            return null;
        }

        return (int) $customer->last_purchase_at->diffInMonths(now());
    }

    // ─── Batch ────────────────────────────────────────────────────────

    /**
     * Recompute LTV metrics for every customer (or a subset).
     */
    public function batchComputeAll(?int $clientId = null, ?\Closure $progress = null): array
    {
        $query = Customer::query();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $customers = $query->cursor();
        $results = ['processed' => 0, 'errors' => 0];

        foreach ($customers as $customer) {
            try {
                $this->computeForCustomer($customer);
                $results['processed']++;

                if ($progress) {
                    $progress($results['processed']);
                }
            } catch (\Throwable $e) {
                $results['errors']++;
                report($e);
            }
        }

        return $results;
    }

    // ─── Internal ─────────────────────────────────────────────────────

    protected function getCustomerOrders(Customer $customer)
    {
        if (!$customer->user_id) {
            return collect();
        }

        return Order::where('user_id', $customer->user_id)
            ->where('status', '!=', 'cancelled')
            ->get(['total', 'created_at']);
    }

    protected function determineTier(float $totalSpent): string
    {
        foreach (self::TIER_THRESHOLDS as $tier => $threshold) {
            if ($totalSpent >= $threshold) {
                return $tier;
            }
        }

        return 'bronze';
    }

    protected function computeEngagement(
        Customer $customer,
        int $orderCount,
        ?string $lastPurchaseAt
    ): float {
        $score = 0.0;

        // Orders contribute to engagement
        $score += $orderCount * self::ENGAGEMENT_WEIGHTS['order'];

        // Recency bonus: orders within last 30 days get boost
        if ($lastPurchaseAt) {
            $daysSincePurchase = now()->diffInDays($lastPurchaseAt);
            if ($daysSincePurchase <= 30) {
                $score += 1.0;
            } elseif ($daysSincePurchase <= 90) {
                $score += 0.5;
            }
        }

        // Ticket activity (count open tickets as engagement)
        $openTicketCount = $customer->tickets()
            ->whereIn('status', ['open', 'pending'])
            ->count();
        $score += $openTicketCount * self::ENGAGEMENT_WEIGHTS['ticket'];

        // Campaign engagement from campaign_log
        $recentOpens = $customer->campaignLogs()
            ->whereNotNull('first_opened_at')
            ->where('first_opened_at', '>=', now()->subDays(90))
            ->count();
        $score += $recentOpens * self::ENGAGEMENT_WEIGHTS['campaign_open'];

        // Review activity
        $reviewCount = $customer->reviews()->count();
        $score += $reviewCount * self::ENGAGEMENT_WEIGHTS['review'];

        // Clamp to 0.00 – 5.00
        return round(min(max($score, 0), 5), 2);
    }
}
