<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    use BelongsToClient;

    protected $table = 'crm_communication_templates';

    protected $fillable = [
        'client_id',
        'name',
        'subject',
        'body',
        'type',
        'variables',
        'trigger_event',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'json',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }
}
