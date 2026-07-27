<?php

namespace Modules\Ecommerce\CRM\Services;

use Modules\Ecommerce\CRM\Models\AdminNotification;

class NotificationService
{
    /**
     * Create a new admin notification.
     */
    public function notify(
        int $clientId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
    ): AdminNotification {
        return AdminNotification::create([
            'client_id' => $clientId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    /**
     * Get unread notifications for a client.
     */
    public function getUnread(int $clientId, int $limit = 10): iterable
    {
        return AdminNotification::forClient($clientId)
            ->unread()
            ->recent($limit)
            ->get();
    }

    /**
     * Get recent notifications for a client (read + unread).
     */
    public function getRecent(int $clientId, int $limit = 25): iterable
    {
        return AdminNotification::forClient($clientId)
            ->recent($limit)
            ->get();
    }

    /**
     * Get unread notification count for a client.
     */
    public function getUnreadCount(int $clientId): int
    {
        return AdminNotification::forClient($clientId)
            ->unread()
            ->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): bool
    {
        return (bool) AdminNotification::where('id', $id)->update(['is_read' => true]);
    }

    /**
     * Mark all notifications as read for a client.
     */
    public function markAllAsRead(int $clientId): int
    {
        return AdminNotification::forClient($clientId)
            ->unread()
            ->update(['is_read' => true]);
    }

    /**
     * Delete old read notifications (cleanup).
     */
    public function prune(int $clientId, int $olderThanDays = 30): int
    {
        return AdminNotification::forClient($clientId)
            ->where('is_read', true)
            ->where('created_at', '<', now()->subDays($olderThanDays))
            ->delete();
    }

    // ─── Convenience factory methods ──────────────────────────────────

    public function ticketCreated(int $clientId, int $ticketId, string $subject): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'ticket_created',
            title: "New Ticket #{$ticketId}",
            body: "{$subject}",
            link: route('ecommerce.admin.crm.tickets') . "?search={$ticketId}",
        );
    }

    public function ticketStatusChanged(int $clientId, int $ticketId, string $subject, string $newStatus): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'ticket_status_changed',
            title: "Ticket #{$ticketId} {$newStatus}",
            body: "{$subject}",
            link: route('ecommerce.admin.crm.tickets') . "?search={$ticketId}",
        );
    }

    public function newOrder(int $clientId, string $orderId, float $total, string $customerName): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'new_order',
            title: "New Order #{$orderId}",
            body: "{$customerName} — ₱" . number_format($total, 2),
            link: route('ecommerce.admin.orders'),
        );
    }

    public function leadStatusChanged(int $clientId, string $leadName, string $newStatus): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'lead_status_changed',
            title: "Lead {$leadName} → {$newStatus}",
            body: "Sales pipeline update",
            link: route('ecommerce.admin.crm.leads.pipeline'),
        );
    }

    public function leadConverted(int $clientId, string $leadName, int $customerId): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'lead_converted',
            title: "Lead Converted: {$leadName}",
            body: "Lead has been converted to a customer",
            link: route('ecommerce.admin.crm.customers.show', $customerId),
        );
    }

    public function reviewPending(int $clientId, string $customerName): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'review_pending',
            title: 'New Review Pending',
            body: "{$customerName} submitted a product review awaiting approval",
            link: route('ecommerce.admin.crm.reviews', ['pending' => 1]),
        );
    }

    public function abandonedCart(int $clientId, string $customerName, float $cartValue): AdminNotification
    {
        return $this->notify(
            clientId: $clientId,
            type: 'abandoned_cart',
            title: 'Abandoned Cart Detected',
            body: "{$customerName} — ₱" . number_format($cartValue, 2),
            link: route('ecommerce.admin.crm.abandoned-carts'),
        );
    }
}
