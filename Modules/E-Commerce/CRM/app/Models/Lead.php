<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'client_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',
        'job_title',
        'status',
        'source',
        'expected_value',
        'actual_value',
        'probability',
        'expected_close_date',
        'last_contacted_at',
        'converted_at',
        'assigned_to',
        'customer_id',
        'notes',
        'activity_log',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'last_contacted_at' => 'datetime',
            'converted_at' => 'datetime',
            'activity_log' => 'json',
        ];
    }

    // ── Pipeline statuses ──

    const STATUSES = [
        'new'          => 'New',
        'contacted'    => 'Contacted',
        'qualified'    => 'Qualified',
        'proposal'     => 'Proposal',
        'negotiation'  => 'Negotiation',
        'won'          => 'Won',
        'lost'         => 'Lost',
    ];

    const PIPELINE_STATUSES = ['new', 'contacted', 'qualified', 'proposal', 'negotiation'];
    const CLOSED_STATUSES   = ['won', 'lost'];

    public function scopeInPipeline($query)
    {
        return $query->whereIn('status', self::PIPELINE_STATUSES);
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, string $name)
    {
        return $query->where('assigned_to', $name);
    }

    // ── Relationships ──

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'lead_id');
    }

    // ── Accessors ──

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unknown';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getIsInPipelineAttribute(): bool
    {
        return in_array($this->status, self::PIPELINE_STATUSES);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES);
    }

    public function getDaysInStageAttribute(): int
    {
        return (int) $this->updated_at->diffInDays(now(), true);
    }

    // ── Helpers ──

    /**
     * Log an activity entry onto the lead's JSON timeline.
     */
    public function logActivity(string $action, ?string $description = null): void
    {
        $log = $this->activity_log ?? [];
        $log[] = [
            'action'      => $action,
            'description' => $description,
            'timestamp'   => now()->toIso8601String(),
        ];
        $this->update(['activity_log' => $log]);
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'new'          => '#3B82F6', // blue
            'contacted'    => '#8B5CF6', // violet
            'qualified'    => '#F59E0B', // amber
            'proposal'     => '#F97316', // orange
            'negotiation'  => '#EF4444', // red
            'won'          => '#22C55E', // green
            'lost'         => '#6B7280', // gray
            default        => '#6B7280',
        };
    }
}
