<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $connection = 'ecommerce';
    protected $table = 'chat_messages';

    protected $fillable = [
        'client_id',
        'user_id',
        'sender_type',
        'sender_id',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Messages newer than a given timestamp (for polling).
     */
    public function scopeSince(Builder $query, string $timestamp): Builder
    {
        return $query->where('created_at', '>', $timestamp);
    }

    /**
     * Conversations = distinct customers with their last message.
     */
    public static function conversationsForClient(int $clientId): array
    {
        $rows = self::selectRaw('DISTINCT ON (user_id) user_id, id, message, sender_type, sender_id, is_read, created_at')
            ->where('client_id', $clientId)
            ->orderBy('user_id')
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('user_id');

        $conversations = [];
        foreach ($rows as $userId => $lastMsg) {
            $unreadCount = self::forClient($clientId)
                ->forUser($userId)
                ->unread()
                ->where('sender_type', 'customer')
                ->count();

            $user = \Modules\Ecommerce\Models\User::find($userId);
            $conversations[] = [
                'user_id' => (int) $userId,
                'customer_name' => $user?->name ?? "Customer #{$userId}",
                'last_message' => $lastMsg->message,
                'last_message_at' => $lastMsg->created_at,
                'last_sender_type' => $lastMsg->sender_type,
                'unread_count' => $unreadCount,
            ];
        }

        // Sort by most recent message
        usort($conversations, fn($a, $b) => strtotime($b['last_message_at']) - strtotime($a['last_message_at']));

        return $conversations;
    }
}
