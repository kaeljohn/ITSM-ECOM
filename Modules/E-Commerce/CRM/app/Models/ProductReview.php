<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use BelongsToClient;

    protected $table = 'crm_product_reviews';

    protected $fillable = [
        'client_id',
        'user_id',
        'listing_id',
        'rating',
        'title',
        'body',
        'approved',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id', 'user_id');
    }
}
