<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Models\CustomerNotification;

class CustomerNotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated customer.
     */
    public function unread(): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $clientId = (int) app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId();

        $notifications = CustomerNotification::forClient($clientId)
            ->forUser($user->id)
            ->unread()
            ->recent(10)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'link' => $n->link,
                    'icon' => $n->icon ?? 'ph-megaphone',
                    'icon_color' => $n->icon_color ?? 'blue',
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at->toIso8601String(),
                ];
            });

        $count = CustomerNotification::forClient($clientId)
            ->forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'notifications' => $notifications,
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $id): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $clientId = (int) app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId();

        $notif = CustomerNotification::forClient($clientId)
            ->forUser($user->id)
            ->where('id', $id)
            ->first();

        if ($notif) {
            $notif->update(['is_read' => true, 'read_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Marked as read.']);
    }

    /**
     * Mark all notifications as read for the authenticated customer.
     */
    public function markAllRead(): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $clientId = (int) app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId();

        CustomerNotification::forClient($clientId)
            ->forUser($user->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All marked as read.']);
    }

    /**
     * SSE stream for real-time notification count updates.
     */
    public function sse(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::guard('ecommerce')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $clientId = (int) app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->clientId();
        $lastCount = $this->getUnreadCount($clientId, $user->id);

        return response()->stream(function () use ($clientId, $user, &$lastCount) {
            set_time_limit(0);

            // Send initial event
            $payload = $this->buildPayload($clientId, $user->id, $lastCount);
            if ($payload) {
                echo "event: notification\ndata: {$payload}\n\n";
                ob_flush(); flush();
            }

            $checkCount = 0;
            while (true) {
                if (connection_aborted()) break;

                sleep(3);
                $checkCount++;

                try {
                    $payload = $this->buildPayload($clientId, $user->id, $lastCount);
                    if ($payload) {
                        echo "event: notification\ndata: {$payload}\n\n";
                        ob_flush(); flush();
                    }
                } catch (\Throwable $e) {
                    $errorPayload = json_encode(['type' => 'error', 'message' => 'Server error, retrying...']);
                    echo "event: error\ndata: {$errorPayload}\n\n";
                    ob_flush(); flush();
                }

                if ($checkCount % 10 === 0) {
                    echo ": keepalive\n\n";
                    ob_flush(); flush();
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function buildPayload(int $clientId, int $userId, int &$lastCount): ?string
    {
        $currentCount = $this->getUnreadCount($clientId, $userId);
        if ($currentCount === $lastCount) return null;

        $lastCount = $currentCount;

        $notifications = CustomerNotification::forClient($clientId)
            ->forUser($userId)
            ->unread()
            ->recent(10)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'link' => $n->link,
                    'icon' => $n->icon ?? 'ph-megaphone',
                    'icon_color' => $n->icon_color ?? 'blue',
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at->toIso8601String(),
                ];
            });

        $payload = json_encode([
            'type' => 'count',
            'count' => $currentCount,
            'notifications' => $notifications,
        ]);

        return $payload ?: null;
    }

    private function getUnreadCount(int $clientId, int $userId): int
    {
        return CustomerNotification::forClient($clientId)
            ->forUser($userId)
            ->unread()
            ->count();
    }
}
