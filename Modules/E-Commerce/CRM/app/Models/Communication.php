<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use BelongsToClient;

    protected $table = 'crm_communications';

    protected $fillable = [
        'client_id',
        'customer_id',
        'type',
        'subject',
        'body',
        'direction',
        'status',
        'reference_type',
        'reference_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
