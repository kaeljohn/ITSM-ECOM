<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignLog extends Model
{
    use BelongsToClient;

    protected $table = 'crm_campaign_log';

    protected $fillable = [
        'client_id',
        'customer_id',
        'campaign_name',
        'campaign_type',
        'subject',
        'body_preview',
        'direction',
        'status',
        'template_id',
        'sent_by_user_id',
        'provider_message_id',
        'provider',
        'sent_at',
        'delivered_at',
        'first_opened_at',
        'first_clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'first_clicked_at' => 'datetime',
        ];
    }

    // ─── Status Tracking ──────────────────────────────────────────────

    const STATUSES = [
        'queued'    => 'Queued',
        'sent'      => 'Sent',
        'delivered' => 'Delivered',
        'opened'    => 'Opened',
        'clicked'   => 'Clicked',
        'bounced'   => 'Bounced',
        'failed'    => 'Failed',
        'spam'      => 'Spam',
    ];

    const TYPES = [
        'email' => 'Email',
        'sms'   => 'SMS',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class, 'campaign_log_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('campaign_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCampaign($query, string $name)
    {
        return $query->where('campaign_name', $name);
    }

    public function scopeSentBetween($query, $start, $end)
    {
        return $query->whereBetween('sent_at', [$start, $end]);
    }

    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'opened', 'clicked']);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['bounced', 'failed', 'spam']);
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('first_opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('first_clicked_at');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->campaign_type] ?? ucfirst($this->campaign_type);
    }

    public function getHasBeenOpenedAttribute(): bool
    {
        return $this->first_opened_at !== null;
    }

    public function getHasBeenClickedAttribute(): bool
    {
        return $this->first_clicked_at !== null;
    }

    /**
     * Campaign "ROI" helper: if the campaign is tied to a coupon or order,
     * this can be overridden by metadata in future phases.
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return in_array($this->status, ['delivered', 'opened', 'clicked']);
    }
}
