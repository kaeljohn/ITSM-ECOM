<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentLog extends Model
{
    use BelongsToClient;

    protected $table = 'crm_consent_log';

    protected $fillable = [
        'client_id',
        'customer_id',
        'channel',
        'action',
        'source',
        'notes',
        'ip_address',
        'user_agent',
        'changed_by_user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    // ─── Constants ────────────────────────────────────────────────────

    const CHANNELS = [
        'email' => 'Email',
        'sms'   => 'SMS',
        'phone' => 'Phone',
        'push'  => 'Push Notification',
    ];

    const ACTIONS = [
        'opt_in'  => 'Opted In',
        'opt_out' => 'Opted Out',
    ];

    const SOURCES = [
        'registration'    => 'Registration',
        'profile_update'  => 'Profile Update',
        'campaign_link'   => 'Campaign Link',
        'manual'          => 'Manual (Staff)',
        'api'             => 'API',
        'checkout'        => 'Checkout',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeOptIns($query)
    {
        return $query->where('action', 'opt_in');
    }

    public function scopeOptOuts($query)
    {
        return $query->where('action', 'opt_out');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('occurred_at');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst($this->channel);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst($this->action);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst($this->source);
    }

    public function getIsOptInAttribute(): bool
    {
        return $this->action === 'opt_in';
    }

    public function getIsOptOutAttribute(): bool
    {
        return $this->action === 'opt_out';
    }
}
