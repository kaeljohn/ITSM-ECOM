<?php

namespace Modules\Ecommerce\CRM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Modules\Ecommerce\CRM\Models\ActivityLog;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\Ticket;
use Modules\Ecommerce\CRM\Models\TicketNote;
use Modules\Ecommerce\CRM\Models\CampaignLog;

class ActivityTimelineService
{
    /**
     * Maximum events returned per query.
     */
    const DEFAULT_LIMIT = 100;

    /**
     * Build a unified, chronologically-sorted activity timeline for a customer.
     *
     * Merges events from: orders, support tickets, ticket notes,
     * campaign logs, product reviews, and the activity_log table.
     *
     * @param  int          $customerId
     * @param  array|null   $types      Filter by type (order, ticket, campaign, review, note)
     * @param  int          $limit
     * @param  string|null  $before     ISO datetime cursor for pagination
     * @return BaseCollection  Sorted collection of timeline event arrays
     */
    public function buildForCustomer(
        int $customerId,
        ?array $types = null,
        int $limit = self::DEFAULT_LIMIT,
        ?string $before = null,
    ): BaseCollection {
        $events = collect();
        $types = $types ?? ['order', 'ticket', 'note', 'campaign', 'review', 'activity'];

        // Need user_id for order queries — fetch only what we need
        $customerUser = Customer::where('id', $customerId)->value('user_id');

        // 1. From the denormalized activity_log table (fast path for pre-recorded events)
        if (in_array('activity', $types)) {
            $query = ActivityLog::where('customer_id', $customerId);

            if ($before) {
                $query->where('occurred_at', '<', $before);
            }

            $query->orderByDesc('occurred_at')->limit($limit);

            foreach ($query->get() as $log) {
                $events->push($this->formatActivityLog($log));
            }
        }

        // 2. From orders (if not already recorded as activity)
        if (in_array('order', $types) && $customerUser) {
            $query = \Modules\Ecommerce\Models\Order::where('user_id', $customerUser)
                ->where('status', '!=', 'cancelled')
                ->with('items');

            if ($before) {
                $query->where('created_at', '<', $before);
            }

            $query->orderByDesc('created_at')->limit($limit);

            foreach ($query->get() as $order) {
                $events->push([
                    'id'           => 'order_' . $order->id,
                    'type'         => 'order',
                    'action'       => 'created',
                    'summary'      => "Order #{$order->getKey()} — ₱" . number_format((float) $order->total, 2),
                    'occurred_at'  => $order->created_at->toIso8601String(),
                    'reference_id' => $order->getKey(),
                    'metadata'     => [
                        'total'          => (float) $order->total,
                        'status'         => $order->status,
                        'payment_status' => $order->payment_status,
                        'item_count'     => $order->items->sum('quantity'),
                    ],
                ]);
            }
        }

        // 3. From support tickets
        if (in_array('ticket', $types)) {
            $query = Ticket::where('customer_id', $customerId);

            if ($before) {
                $query->where('created_at', '<', $before);
            }

            $query->orderByDesc('created_at')->limit($limit);

            foreach ($query->get() as $ticket) {
                $events->push([
                    'id'           => 'ticket_' . $ticket->id,
                    'type'         => 'ticket',
                    'action'       => $ticket->status === 'resolved' ? 'resolved' : 'created',
                    'summary'      => "Ticket: {$ticket->subject} [{$ticket->status_label}]",
                    'occurred_at'  => $ticket->created_at->toIso8601String(),
                    'reference_id' => $ticket->id,
                    'metadata'     => [
                        'status'   => $ticket->status,
                        'priority' => $ticket->priority,
                        'category' => $ticket->category,
                    ],
                ]);
            }
        }

        // 4. From ticket notes
        if (in_array('note', $types)) {
            $query = TicketNote::whereHas('ticket', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            });

            if ($before) {
                $query->where('created_at', '<', $before);
            }

            $query->orderByDesc('created_at')->limit($limit);

            foreach ($query->get() as $note) {
                $events->push([
                    'id'           => 'note_' . $note->id,
                    'type'         => 'note',
                    'action'       => $note->is_internal ? 'internal_note' : 'reply',
                    'summary'      => $note->excerpt(120),
                    'occurred_at'  => $note->created_at->toIso8601String(),
                    'reference_id' => $note->ticket_id,
                    'metadata'     => [
                        'author'      => $note->author_name,
                        'is_internal' => $note->is_internal,
                        'note_type'   => $note->note_type,
                    ],
                ]);
            }
        }

        // 5. From campaign logs
        if (in_array('campaign', $types)) {
            $query = CampaignLog::where('customer_id', $customerId);

            if ($before) {
                $query->where('sent_at', '<', $before);
            }

            $query->orderByDesc('sent_at')->limit($limit);

            foreach ($query->get() as $campaign) {
                $events->push([
                    'id'           => 'campaign_' . $campaign->id,
                    'type'         => 'campaign',
                    'action'       => $campaign->status,
                    'summary'      => "{$campaign->type_label}: {$campaign->subject}",
                    'occurred_at'  => ($campaign->sent_at ?? $campaign->created_at)->toIso8601String(),
                    'reference_id' => $campaign->id,
                    'metadata'     => [
                        'campaign_name' => $campaign->campaign_name,
                        'status'        => $campaign->status,
                        'type'          => $campaign->campaign_type,
                        'opened'        => $campaign->has_been_opened,
                        'clicked'       => $campaign->has_been_clicked,
                    ],
                ]);
            }
        }

        // Sort all events by occurred_at descending
        $sorted = $events->sortByDesc('occurred_at')->values();

        // Trim to limit
        return $sorted->take($limit);
    }

    // ─── Recording ────────────────────────────────────────────────────

    /**
     * Record a new event into the activity_log table.
     *
     * This is the primary way to log events. The timeline service
     * also reads from source tables, but recording here provides
     * a fast denormalized path.
     */
    public function recordEvent(
        int $customerId,
        string $type,
        string $action,
        ?string $summary = null,
        mixed $reference = null,
        ?array $metadata = null,
        ?\DateTimeInterface $occurredAt = null,
    ): ActivityLog {
        return ActivityLog::record(
            customerId: $customerId,
            type: $type,
            action: $action,
            summary: $summary,
            reference: $reference,
            metadata: $metadata,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Batch-record multiple events (e.g. after order import).
     */
    public function batchRecord(array $events): int
    {
        $inserted = 0;

        foreach ($events as $event) {
            try {
                $this->recordEvent(
                    customerId: $event['customer_id'],
                    type: $event['type'],
                    action: $event['action'],
                    summary: $event['summary'] ?? null,
                    reference: $event['reference'] ?? null,
                    metadata: $event['metadata'] ?? null,
                    occurredAt: $event['occurred_at'] ?? null,
                );
                $inserted++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $inserted;
    }

    /**
     * Backfill the activity_log table from source tables for a customer.
     * Uses proper morph class names so the polymorphic reference resolves.
     */
    public function backfillForCustomer(int $customerId): int
    {
        $events = $this->buildForCustomer($customerId, limit: 1000);

        // Morph map: event type strings → actual model classes
        $morphMap = [
            'order'    => \Modules\Ecommerce\Models\Order::class,
            'ticket'   => Ticket::class,
            'note'     => TicketNote::class,
            'campaign' => CampaignLog::class,
        ];

        $recorded = 0;
        foreach ($events as $event) {
            try {
                $morphClass = $morphMap[$event['type']] ?? $event['type'];

                ActivityLog::create([
                    'client_id'      => null, // resolved by BelongsToClient trait
                    'customer_id'    => $customerId,
                    'type'           => $event['type'],
                    'action'         => $event['action'],
                    'summary'        => $event['summary'],
                    'reference_type' => $morphClass,
                    'reference_id'   => $event['reference_id'],
                    'metadata'       => $event['metadata'] ?? null,
                    'occurred_at'    => $event['occurred_at'],
                ]);
                $recorded++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $recorded;
    }

    // ─── Formatting ───────────────────────────────────────────────────

    protected function formatActivityLog(ActivityLog $log): array
    {
        return [
            'id'           => 'activity_' . $log->id,
            'type'         => $log->type,
            'action'       => $log->action,
            'summary'      => $log->summary,
            'occurred_at'  => $log->occurred_at->toIso8601String(),
            'reference_id' => $log->reference_id,
            'metadata'     => $log->metadata,
        ];
    }
}
