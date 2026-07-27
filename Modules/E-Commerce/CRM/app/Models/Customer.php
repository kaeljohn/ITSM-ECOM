<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $table = 'crm_customers';

    protected $fillable = [
        'client_id',
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'source',
        'total_spent',
        'order_count',
        'last_purchase_at',
        'average_order_value',
        'metadata',
        'notes',

        // Customer Health & Engagement (added by 2026_08_01_000001)
        'engagement_score',
        'churn_risk',
        'last_engaged_at',

        // Compliance & Consent (added by 2026_08_01_000001)
        'opt_in_email',
        'opt_in_sms',
        'opted_in_at',

        // Other processes may set these (forge_points, tier)
        'forge_points',
        'total_forge_points_earned',
        'tier',
    ];

    protected function casts(): array
    {
        return [
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'engagement_score' => 'decimal:2',
            'opt_in_email' => 'boolean',
            'opt_in_sms' => 'boolean',
            'last_purchase_at' => 'datetime',
            'opted_in_at' => 'datetime',
            'last_engaged_at' => 'datetime',
            'metadata' => 'json',
            'forge_points' => 'integer',
            'total_forge_points_earned' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'crm_customer_tags', 'customer_id', 'tag_id');
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(Segment::class, 'crm_customer_segments', 'customer_id', 'segment_id');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'user_id', 'user_id');
    }

    public function activityLog(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'customer_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function openTickets(): HasMany
    {
        return $this->tickets()->whereIn('status', ['open', 'pending']);
    }

    public function ticketNotes(): HasManyThrough
    {
        return $this->hasManyThrough(TicketNote::class, Ticket::class, 'customer_id', 'ticket_id');
    }

    public function campaignLogs(): HasMany
    {
        return $this->hasMany(CampaignLog::class, 'customer_id');
    }

    public function campaignEvents(): HasManyThrough
    {
        return $this->hasManyThrough(CampaignEvent::class, CampaignLog::class, 'customer_id', 'campaign_log_id');
    }

    public function consentLogs(): HasMany
    {
        return $this->hasMany(ConsentLog::class, 'customer_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unknown';
    }

    public function getChurnRiskLabelAttribute(): string
    {
        return match ($this->churn_risk) {
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            default  => ucfirst($this->churn_risk),
        };
    }

    public function getChurnRiskColorAttribute(): string
    {
        return match ($this->churn_risk) {
            'low'    => '#22C55E',
            'medium' => '#F59E0B',
            'high'   => '#EF4444',
            default  => '#6B7280',
        };
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'bronze'   => 'Bronze',
            'silver'   => 'Silver',
            'gold'     => 'Gold',
            'platinum' => 'Platinum',
            default    => ucfirst($this->tier),
        };
    }

    public function getTierColorAttribute(): string
    {
        return match ($this->tier) {
            'bronze'   => '#CD7F32',
            'silver'   => '#A0AEC0',
            'gold'     => '#F59E0B',
            'platinum' => '#718096',
            default    => '#6B7280',
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeHighValue($query, float $threshold = 5000)
    {
        return $query->where('total_spent', '>=', $threshold);
    }

    public function scopeRecent($query, ?int $days = 30)
    {
        return $query->where('last_purchase_at', '>=', now()->subDays($days));
    }

    public function scopeByChurnRisk($query, string $risk)
    {
        return $query->where('churn_risk', $risk);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeOptedIn($query, ?string $channel = null)
    {
        if ($channel === 'email') {
            return $query->where('opt_in_email', true);
        }
        if ($channel === 'sms') {
            return $query->where('opt_in_sms', true);
        }
        return $query->where(function ($q) {
            $q->where('opt_in_email', true)->orWhere('opt_in_sms', true);
        });
    }

    public function scopeEngagedBetween($query, $start, $end)
    {
        return $query->whereBetween('last_engaged_at', [$start, $end]);
    }

    public function scopeNeedsEngagement($query, ?int $days = 60)
    {
        return $query->where(function ($q) use ($days) {
            $q->where('last_engaged_at', '<', now()->subDays($days))
              ->orWhereNull('last_engaged_at');
        });
    }
}
