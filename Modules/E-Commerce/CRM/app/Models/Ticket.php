<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'crm_tickets';

    protected $fillable = [
        'client_id',
        'customer_id',
        'subject',
        'description',
        'status',
        'priority',
        'channel',
        'assigned_to',
        'assigned_to_user_id',
        'order_id',
        'category',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // ─── Status Constants ─────────────────────────────────────────────

    const STATUSES = [
        'open'     => 'Open',
        'pending'  => 'Pending',
        'resolved' => 'Resolved',
        'closed'   => 'Closed',
    ];

    const PRIORITIES = [
        'low'    => 'Low',
        'normal' => 'Normal',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ];

    const CHANNELS = [
        'email'  => 'Email',
        'chat'   => 'Chat',
        'phone'  => 'Phone',
        'portal' => 'Portal',
        'social' => 'Social Media',
    ];

    const ACTIVE_STATUSES = ['open', 'pending'];

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class, 'ticket_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->notes()->where('is_internal', true);
    }

    public function customerReplies(): HasMany
    {
        return $this->notes()->where('is_internal', false);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to_user_id');
    }

    public function scopeUrgentFirst($query)
    {
        return $query->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 ELSE 4 END");
    }

    public function scopeNewestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low'    => '#6B7280',
            'normal' => '#3B82F6',
            'high'   => '#F59E0B',
            'urgent' => '#EF4444',
            default  => '#6B7280',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open'     => '#3B82F6',
            'pending'  => '#F59E0B',
            'resolved' => '#22C55E',
            'closed'   => '#6B7280',
            default    => '#6B7280',
        };
    }

    /**
     * Time in hours since the ticket was created (for SLA monitoring).
     */
    public function getAgeInHoursAttribute(): float
    {
        return $this->created_at->diffInRealHours(now(), true);
    }
}
