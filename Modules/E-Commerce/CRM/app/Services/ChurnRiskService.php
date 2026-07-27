<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;

class ChurnRiskService
{
    /**
     * Risk thresholds (in days).
     */
    const RISK_DAYS = [
        'high'   => 120,  // No purchase in 120+ days
        'medium' => 60,   // No purchase in 60-119 days
        'low'    => 0,    // Purchased within 60 days
    ];

    const ENGAGEMENT_RISK_DAYS = [
        'high'   => 180,  // No engagement in 180+ days
        'medium' => 90,   // No engagement in 90-179 days
    ];

    /**
     * Evaluate churn risk for a single customer and persist it.
     */
    public function evaluateForCustomer(Customer $customer): string
    {
        $risk = $this->calculateRisk($customer);

        $customer->update(['churn_risk' => $risk]);

        return $risk;
    }

    /**
     * Batch-evaluate churn risk for all customers.
     */
    public function batchEvaluateAll(?int $clientId = null, ?\Closure $progress = null): array
    {
        $query = Customer::query();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $results = [
            'processed' => 0,
            'errors'    => 0,
            'low'       => 0,
            'medium'    => 0,
            'high'      => 0,
        ];

        foreach ($query->cursor() as $customer) {
            try {
                $risk = $this->evaluateForCustomer($customer);
                $results[$risk]++;
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

    // ─── Core Algorithm ───────────────────────────────────────────────

    /**
     * Calculate churn risk based on:
     * 1. Recency of last purchase
     * 2. Recency of last engagement (any interaction)
     * 3. Order frequency trend (declining = higher risk)
     * 4. Historical order count (new customers with low engagement = higher risk)
     */
    protected function calculateRisk(Customer $customer): string
    {
        $signals = [];

        // ── Signal 1: Purchase recency ────────────────────────────────
        if (!$customer->last_purchase_at) {
            // Never purchased → at risk of churning before first conversion
            $signals[] = 'medium';
        } else {
            $daysSincePurchase = (int) $customer->last_purchase_at->diffInDays(now());

            if ($daysSincePurchase >= self::RISK_DAYS['high']) {
                $signals[] = 'high';
            } elseif ($daysSincePurchase >= self::RISK_DAYS['medium']) {
                $signals[] = 'medium';
            } else {
                $signals[] = 'low';
            }
        }

        // ── Signal 2: Engagement recency ──────────────────────────────
        if ($customer->last_engaged_at) {
            $daysSinceEngagement = (int) $customer->last_engaged_at->diffInDays(now());

            if ($daysSinceEngagement >= self::ENGAGEMENT_RISK_DAYS['high']) {
                $signals[] = 'high';
            } elseif ($daysSinceEngagement >= self::ENGAGEMENT_RISK_DAYS['medium']) {
                $signals[] = 'medium';
            } else {
                $signals[] = 'low';
            }
        } elseif ($customer->last_purchase_at) {
            // Purchased but never engaged otherwise → slight risk
            $signals[] = 'medium';
        }

        // ── Signal 3: Frequency decline (only if they have history) ───
        if ($customer->order_count >= 3 && $customer->last_purchase_at) {
            $firstOrderDate = $customer->created_at; // approximation
            $activeSpanMonths = max(1, $firstOrderDate->diffInMonths($customer->last_purchase_at));
            $monthlyRate = $customer->order_count / $activeSpanMonths;

            if ($monthlyRate < 0.25) {
                // Fewer than 1 order per 4 months → declining
                $signals[] = 'medium';
            }
        } elseif ($customer->order_count === 1 && $customer->last_purchase_at) {
            // Single-purchase customers: if it was a while ago, flag them
            $daysSincePurchase = (int) $customer->last_purchase_at->diffInDays(now());
            if ($daysSincePurchase >= 90) {
                $signals[] = 'medium';
            }
        }

        // ── Signal 4: Low engagement score ────────────────────────────
        if ($customer->engagement_score !== null && $customer->engagement_score < 1.0) {
            $signals[] = 'medium';
        }

        // ── Aggregate signals ─────────────────────────────────────────
        return $this->aggregateSignals($signals);
    }

    /**
     * Aggregate multiple risk signals into a final classification.
     * - If any signal is 'high', result is 'high'
     * - If 2+ signals are 'medium', result is 'high'
     * - If 1 signal is 'medium', result is 'medium'
     * - Otherwise 'low'
     */
    protected function aggregateSignals(array $signals): string
    {
        if (empty($signals)) {
            return 'low';
        }

        $highCount = count(array_filter($signals, fn ($s) => $s === 'high'));
        $mediumCount = count(array_filter($signals, fn ($s) => $s === 'medium'));

        if ($highCount > 0) {
            return 'high';
        }

        if ($mediumCount >= 2) {
            return 'high';
        }

        if ($mediumCount === 1) {
            return 'medium';
        }

        return 'low';
    }
}
