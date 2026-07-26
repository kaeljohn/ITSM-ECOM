<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use BelongsToClient;

    protected $table = 'crm_tags';

    protected $fillable = [
        'client_id',
        'name',
        'color',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'crm_customer_tags', 'tag_id', 'customer_id');
    }
}
