<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    use BelongsToClient;

    protected $table = 'crm_coupon_redemptions';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'coupon_id',
        'order_id',
        'user_id',
        'discount_amount',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'redeemed_at' => 'datetime',
        ];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
