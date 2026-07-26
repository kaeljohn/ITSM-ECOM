<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected function casts(): array
    {
        return [
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'last_purchase_at' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'crm_customer_tags', 'customer_id', 'tag_id');
    }

    public function segments()
    {
        return $this->belongsToMany(Segment::class, 'crm_customer_segments', 'customer_id', 'segment_id');
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'customer_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'user_id', 'user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unknown';
    }

    public function scopeHighValue($query, float $threshold = 5000)
    {
        return $query->where('total_spent', '>=', $threshold);
    }

    public function scopeRecent($query, ?int $days = 30)
    {
        return $query->where('last_purchase_at', '>=', now()->subDays($days));
    }
}
