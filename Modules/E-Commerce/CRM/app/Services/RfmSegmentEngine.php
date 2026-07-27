<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\Segment;

class RfmSegmentEngine
{
    /**
     * RFM segment definitions with scoring ranges.
     *
     * Each segment has:
     *  - label: Human-readable name
     *  - rfm_pattern: [R_min, R_max, F_min, F_max, M_min, M_max]  (1–5 scale)
     *  - color: UI badge color
     */
    const SEGMENTS = [
        'champions'          => ['label' => 'Champions',          'rfm' => [4,5, 4,5, 4,5], 'color' => '#22C55E'],
        'loyal'              => ['label' => 'Loyal Customers',    'rfm' => [2,5, 3,5, 3,5], 'color' => '#3B82F6'],
        'potential'          => ['label' => 'Potential Loyalists','rfm' => [3,5, 1,3, 1,3], 'color' => '#8B5CF6'],
        'recent'             => ['label' => 'New Customers',      'rfm' => [4,5, 1,1, 1,3], 'color' => '#06B6D4'],
        'promising'          => ['label' => 'Promising',          'rfm' => [3,4, 1,1, 1,2], 'color' => '#F59E0B'],
        'needs_attention'    => ['label' => 'Needs Attention',    'rfm' => [2,3, 1,2, 1,2], 'color' => '#F97316'],
        'about_to_sleep'     => ['label' => 'About to Sleep',     'rfm' => [1,2, 1,2, 1,2], 'color' => '#EF4444'],
        'at_risk'            => ['label' => 'At Risk',            'rfm' => [1,2, 2,5, 2,5], 'color' => '#DC2626'],
        'cant_lose'          => ['label' => "Can't Lose Them",    'rfm' => [1,2, 4,5, 4,5], 'color' => '#991B1B'],
        'hibernating'        => ['label' => 'Hibernating',        'rfm' => [1,2, 1,2, 1,5], 'color' => '#6B7280'],
        'lost'               => ['label' => 'Lost',               'rfm' => [1,1, 1,1, 1,5], 'color' => '#9CA3AF'],
    ];

    // ─── Single Customer ──────────────────────────────────────────────

    /**
     * Compute RFM scores (1-5) for a single customer.
     */
    public function scoreCustomer(Customer $customer): array
    {
        $recency = $this->scoreRecency($customer);
        $frequency = $this->scoreFrequency($customer);
        $monetary = $this->scoreMonetary($customer);

        return [
            'recency'   => $recency,
            'frequency' => $frequency,
            'monetary'  => $monetary,
            'total'     => $recency + $frequency + $monetary,
            'segment'   => $this->classifySegment($recency, $frequency, $monetary),
        ];
    }

    /**
     * Determine which RFM segment a customer falls into.
     */
    public function classifySegment(int $recency, int $frequency, int $monetary): array
    {
        foreach (self::SEGMENTS as $key => $seg) {
            [$rMin, $rMax, $fMin, $fMax, $mMin, $mMax] = $seg['rfm'];

            if (
                $recency >= $rMin && $recency <= $rMax &&
                $frequency >= $fMin && $frequency <= $fMax &&
                $monetary >= $mMin && $monetary <= $mMax
            ) {
                return [
                    'slug'  => $key,
                    'label' => $seg['label'],
                    'color' => $seg['color'],
                ];
            }
        }

        // Fallback
        return [
            'slug'  => 'unknown',
            'label' => 'Unclassified',
            'color' => '#9CA3AF',
        ];
    }

    // ─── Batch Processing ─────────────────────────────────────────────

    /**
     * Re-score all customers and auto-assign them to dynamic segments.
     */
    public function batchScoreAll(?int $clientId = null, ?\Closure $progress = null): array
    {
        $query = Customer::query();
        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $customers = $query->cursor();
        $results = ['processed' => 0, 'errors' => 0, 'segment_counts' => []];

        foreach ($customers as $customer) {
            try {
                $scores = $this->scoreCustomer($customer);
                $segmentSlug = $scores['segment']['slug'];

                $results['segment_counts'][$segmentSlug] =
                    ($results['segment_counts'][$segmentSlug] ?? 0) + 1;

                // Sync the customer to the auto-segment that matches their RFM
                $this->syncCustomerToAutoSegment($customer, $segmentSlug);

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

    /**
     * Evaluate ALL auto-segments and re-assign customers whose RFM
     * profile matches the segment criteria.
     */
    public function evaluateAutoSegments(?int $clientId = null): array
    {
        $autoSegments = Segment::where('is_auto', true);

        if ($clientId) {
            $autoSegments->where('client_id', $clientId);
        }

        $results = ['segments_evaluated' => 0, 'customers_assigned' => 0];

        foreach ($autoSegments->cursor() as $segment) {
            $criteria = $segment->criteria;

            if (!$criteria || !isset($criteria['rfm'])) {
                continue;
            }

            $matching = $this->findCustomersMatchingCriteria($criteria);
            $segment->customers()->sync($matching->pluck('id'));

            $results['segments_evaluated']++;
            $results['customers_assigned'] += $matching->count();
        }

        return $results;
    }

    // ─── Scoring Methods ──────────────────────────────────────────────

    /**
     * Recency: 5 = purchased within 30 days, 1 = 365+ days / no purchases.
     */
    protected function scoreRecency(Customer $customer): int
    {
        if (!$customer->last_purchase_at) {
            return 1;
        }

        $days = (int) $customer->last_purchase_at->diffInDays(now());

        return match (true) {
            $days <= 30  => 5,
            $days <= 90  => 4,
            $days <= 180 => 3,
            $days <= 365 => 2,
            default       => 1,
        };
    }

    /**
     * Frequency: 5 = 20+ orders, 1 = 0 orders.
     */
    protected function scoreFrequency(Customer $customer): int
    {
        $count = $customer->order_count;

        return match (true) {
            $count >= 20 => 5,
            $count >= 10 => 4,
            $count >= 5  => 3,
            $count >= 2  => 2,
            $count >= 1  => 1,
            default       => 1,
        };
    }

    /**
     * Monetary: 5 = ₱100k+ spent, 1 = < ₱1k / no spend.
     */
    protected function scoreMonetary(Customer $customer): int
    {
        $total = $customer->total_spent;

        return match (true) {
            $total >= 100000 => 5,
            $total >= 50000  => 4,
            $total >= 10000  => 3,
            $total >= 1000   => 2,
            $total > 0       => 1,
            default           => 1,
        };
    }

    // ─── Internal Helpers ─────────────────────────────────────────────

    protected function syncCustomerToAutoSegment(Customer $customer, string $segmentSlug): void
    {
        // Find or create the auto-segment for this slug + client
        $segment = Segment::firstOrCreate(
            [
                'client_id' => $customer->client_id,
                'slug'      => $segmentSlug,
            ],
            [
                'name'        => self::SEGMENTS[$segmentSlug]['label'] ?? ucfirst($segmentSlug),
                'description' => 'Auto-assigned RFM segment',
                'is_auto'     => true,
                'criteria'    => self::SEGMENTS[$segmentSlug] ?? null,
            ]
        );

        // Collect all current RFM auto-segments for this customer
        $autoSegmentIds = Segment::where('is_auto', true)
            ->where('client_id', $customer->client_id)
            ->pluck('id')
            ->toArray();

        // Detach from all RFM auto-segments first, then attach the matching one
        // This ensures customers don't accumulate in old segments as their RFM changes
        $customer->segments()->detach($autoSegmentIds);
        $customer->segments()->attach($segment->id);
    }

    protected function findCustomersMatchingCriteria(array $criteria)
    {
        $query = Customer::query();

        if (isset($criteria['rfm'])) {
            $rfm = $criteria['rfm'];
            // rfm = [R_min, R_max, F_min, F_max, M_min, M_max]
            // We can't query by RFM directly since it's computed,
            // so we approximate with the underlying metrics
            if (isset($rfm[0], $rfm[1])) {
                // Recency approximation via last_purchase_at
                // Score-to-day mapping: 5=0-30d, 4=31-90d, 3=91-180d, 2=181-365d, 1=365d+
                // For a range [min, max], the overall bound is:
                //   outer = upper bound from the LOWEST score (most days)
                //   inner = lower bound from the HIGHEST score (fewest days)
                $scoreToUpper = [1 => PHP_INT_MAX, 2 => 365, 3 => 180, 4 => 90, 5 => 30];
                $scoreToLower = [1 => 365, 2 => 180, 3 => 90, 4 => 30, 5 => 0];

                $upperDays = $scoreToUpper[$rfm[0]] ?? PHP_INT_MAX;
                $lowerDays = $scoreToLower[$rfm[1]] ?? 0;

                $query->where(function ($q) use ($upperDays, $lowerDays, $rfm) {
                    if ($lowerDays > 0) {
                        $q->where('last_purchase_at', '<=', now()->subDays($lowerDays));
                    }
                    $q->where('last_purchase_at', '>=', now()->subDays($upperDays));
                });
            }

            if (isset($rfm[2], $rfm[3])) {
                // Frequency approximation via order_count
                $freqRanges = [0, 1, 2, 5, 10, 20];
                $query->whereBetween('order_count', [
                    $freqRanges[$rfm[2]] ?? 0,
                    $freqRanges[$rfm[3]] ?? PHP_INT_MAX,
                ]);
            }

            if (isset($rfm[4], $rfm[5])) {
                // Monetary approximation via total_spent
                $monRanges = [0, 1, 1000, 10000, 50000, 100000];
                $query->whereBetween('total_spent', [
                    $monRanges[$rfm[4]] ?? 0,
                    $monRanges[$rfm[5]] ?? PHP_INT_MAX,
                ]);
            }
        }

        if (isset($criteria['tags'])) {
            $query->whereHas('tags', function ($q) use ($criteria) {
                $q->whereIn('crm_tags.name', (array) $criteria['tags']);
            });
        }

        if (isset($criteria['sources'])) {
            $query->whereIn('source', (array) $criteria['sources']);
        }

        if (isset($criteria['min_spent'])) {
            $query->where('total_spent', '>=', $criteria['min_spent']);
        }

        if (isset($criteria['max_spent'])) {
            $query->where('total_spent', '<=', $criteria['max_spent']);
        }

        return $query;
    }
}
