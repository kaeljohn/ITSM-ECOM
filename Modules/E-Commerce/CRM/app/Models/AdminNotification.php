<?php

namespace Modules\Ecommerce\CRM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $connection = 'ecommerce';
    protected $table = 'crm_admin_notifications';

    protected $fillable = [
        'client_id',
        'type',
        'title',
        'body',
        'link',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Mark this notification as read.
     */
    public function markAsRead(): static
    {
        $this->update(['is_read' => true]);
        return $this;
    }

    /**
     * Get the notification icon class based on type.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'ticket_created', 'ticket_updated', 'ticket_status_changed' => 'ph-ticket',
            'new_order', 'order_status_changed' => 'ph-shopping-cart',
            'lead_created', 'lead_status_changed', 'lead_converted' => 'ph-currency-circle-dollar',
            'new_customer' => 'ph-user-plus',
            'review_pending', 'review_approved' => 'ph-star',
            'abandoned_cart' => 'ph-cart-x',
            'campaign_sent' => 'ph-megaphone',
            'system' => 'ph-gear',
            default => 'ph-bell',
        };
    }

    /**
     * Get the icon background color class based on type.
     */
    public function getIconColorAttribute(): string
    {
        return match ($this->type) {
            'ticket_created', 'ticket_updated', 'ticket_status_changed' => 'blue',
            'new_order', 'order_status_changed' => 'green',
            'lead_created', 'lead_status_changed', 'lead_converted' => 'amber',
            'new_customer' => 'teal',
            'review_pending' => 'purple',
            'review_approved' => 'green',
            'abandoned_cart' => 'red',
            'campaign_sent' => 'purple',
            'system' => 'gray',
            default => 'blue',
        };
    }
}
