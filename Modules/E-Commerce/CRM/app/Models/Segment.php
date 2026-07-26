<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    use BelongsToClient;

    protected $table = 'crm_segments';

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'description',
        'criteria',
        'is_auto',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'json',
            'is_auto' => 'boolean',
        ];
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'crm_customer_segments', 'segment_id', 'customer_id');
    }
}
