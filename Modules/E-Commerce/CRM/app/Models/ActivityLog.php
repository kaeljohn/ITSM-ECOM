<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use BelongsToClient;

    protected $table = 'crm_activity_log';

    protected $fillable = [
        'client_id',
        'customer_id',
        'type',
        'action',
        'summary',
        'reference_type',
        'reference_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'occurred_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Polymorphic reference to the source entity (order, ticket, campaign, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, ?int $limit = 50)
    {
        return $query->orderByDesc('occurred_at')->limit($limit);
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    // ─── Factory Helpers ──────────────────────────────────────────────

    /**
     * Create a timeline entry quickly in one static call.
     */
    public static function record(
        int $customerId,
        string $type,
        string $action,
        ?string $summary = null,
        ?Model $reference = null,
        ?array $metadata = null,
        ?\DateTimeInterface $occurredAt = null,
    ): self {
        return static::create([
            'customer_id' => $customerId,
            'type' => $type,
            'action' => $action,
            'summary' => $summary,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference ? $reference->getKey() : null,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
