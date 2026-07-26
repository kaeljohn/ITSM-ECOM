<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use BelongsToClient;

    protected $table = 'crm_coupons';

    protected $fillable = [
        'client_id',
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'max_uses',
        'per_user_limit',
        'usage_count',
        'segment_id',
        'status',
        'starts_at',
        'expires_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_count' => 'integer',
            'per_user_limit' => 'integer',
            'max_uses' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            });
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('usage_count', '<', 'max_uses');
            });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsFullyUsedAttribute(): bool
    {
        return $this->max_uses && $this->usage_count >= $this->max_uses;
    }

    public function getDisplayValueAttribute(): string
    {
        return $this->type === 'percentage'
            ? $this->value . '%'
            : '₱' . number_format($this->value, 0);
    }
}
