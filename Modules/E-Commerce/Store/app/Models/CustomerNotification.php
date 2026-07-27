<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $connection = 'ecommerce';
    protected $table = 'customer_notifications';

    protected $fillable = [
        'client_id',
        'user_id',
        'type',
        'title',
        'body',
        'link',
        'icon',
        'icon_color',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Notifications visible to a specific user:
     * - Broadcast (user_id is null) OR
     * - Targeted to this specific user
     */
    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        if ($userId) {
            return $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        }

        return $query->whereNull('user_id');
    }
}
