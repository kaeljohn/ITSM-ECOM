<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNote extends Model
{
    use BelongsToClient;

    protected $table = 'crm_ticket_notes';

    protected $fillable = [
        'client_id',
        'ticket_id',
        'author_id',
        'author_name',
        'body',
        'mentions',
        'is_internal',
        'note_type',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'json',
            'is_internal' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeCustomerVisible($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('note_type', $type);
    }

    // ─── Accessors ────────────────────────────────────────────────────

    /**
     * Parse @mentions into a structured array.
     */
    public function getMentionedUsersAttribute(): array
    {
        return $this->mentions ?? [];
    }

    /**
     * Extract plain text from body for search indexing / summaries.
     */
    public function excerpt(int $length = 200): string
    {
        $text = strip_tags($this->body);

        return strlen($text) > $length
            ? substr($text, 0, $length) . '…'
            : $text;
    }
}
