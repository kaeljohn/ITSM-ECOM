<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEvent extends Model
{
    use BelongsToClient;

    protected $table = 'crm_campaign_events';

    protected $fillable = [
        'client_id',
        'campaign_log_id',
        'event_type',
        'payload',
        'user_agent',
        'ip_address',
        'device_type',
        'country',
        'city',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    // ─── Event Types ──────────────────────────────────────────────────

    const EVENT_TYPES = [
        'delivered'    => 'Delivered',
        'opened'       => 'Opened',
        'clicked'      => 'Clicked',
        'bounced'      => 'Bounced',
        'complained'   => 'Complained',
        'unsubscribed' => 'Unsubscribed',
        'failed'       => 'Failed',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function campaignLog(): BelongsTo
    {
        return $this->belongsTo(CampaignLog::class, 'campaign_log_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeByEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeOpens($query)
    {
        return $query->where('event_type', 'opened');
    }

    public function scopeClicks($query)
    {
        return $query->where('event_type', 'clicked');
    }

    public function scopeBounces($query)
    {
        return $query->where('event_type', 'bounced');
    }

    public function scopeRecent($query, ?int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getEventTypeLabelAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? ucfirst($this->event_type);
    }

    /**
     * The URL that was clicked (only relevant for 'clicked' events).
     */
    public function getClickedUrlAttribute(): ?string
    {
        return $this->event_type === 'clicked' ? $this->payload : null;
    }

    /**
     * The bounce error message (only relevant for 'bounced' events).
     */
    public function getBounceReasonAttribute(): ?string
    {
        return $this->event_type === 'bounced' ? $this->payload : null;
    }
}
