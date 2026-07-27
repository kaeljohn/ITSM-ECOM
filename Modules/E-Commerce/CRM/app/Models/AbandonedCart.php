<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    use BelongsToClient;

    protected $table = 'crm_abandoned_carts';

    protected $fillable = [
        'client_id',
        'user_id',
        'cart_id',
        'email',
        'cart_total',
        'items_summary',
        'status',
        'abandoned_at',
        'recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_total' => 'decimal:2',
            'items_summary' => 'json',
            'abandoned_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id', 'user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecoverable($query, int $withinHours = 24)
    {
        return $query->where('status', 'pending')
            ->where('abandoned_at', '>=', now()->subHours($withinHours));
    }
}
