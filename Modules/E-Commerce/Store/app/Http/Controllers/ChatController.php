<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Models\ChatMessage;
use Modules\Ecommerce\Models\CustomerNotification;
use Modules\Ecommerce\Support\EcommerceClientContext;

class ChatController extends Controller
{
    /**
     * Get the client ID from the current request context.
     */
    private function getClientId(): ?int
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        if ($clientId) {
            return $clientId;
        }

        $company = request()->attributes->get('ecommerce_company');
        return $company?->id;
    }

    /**
     * Get the store slug from the request context.
     */
    private function getStore(): string
    {
        return request()->route('store')
            ?? optional(request()->attributes->get('ecommerce_company'))->ecommerce_slug
            ?? 'store';
    }

    // ================================================================
    //  ADMIN ENDPOINTS
    // ================================================================

    /**
     * GET /ecommerce-admin/crm/api/chat/conversations
     * List all customers who have chat messages (with last message + unread count).
     */
    public function adminConversations()
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Client not resolved'], 422);
        }

        $conversations = ChatMessage::conversationsForClient($clientId);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * GET /ecommerce-admin/crm/api/chat/{userId}
     * Get all messages with a customer (paginated).
     */
    public function adminMessages(Request $request, int $userId)
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Client not resolved'], 422);
        }

        $messages = ChatMessage::forClient($clientId)
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->paginate(50);

        // Mark admin-viewed messages as read
        ChatMessage::forClient($clientId)
            ->forUser($userId)
            ->unread()
            ->where('sender_type', 'customer')
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * POST /ecommerce-admin/crm/api/chat/{userId}
     * Admin sends a message to a customer.
     */
    public function adminSend(Request $request, int $userId)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $clientId = $this->getClientId();
        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Client not resolved'], 422);
        }

        $admin = auth('ecommerce_admin')->user();

        $msg = ChatMessage::create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'sender_type' => 'admin',
            'sender_id' => $admin?->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Notify the customer via notification bell
        try {
            $store = $this->getStore();
            $storefrontName = optional(\App\Models\Company::find($clientId))->company_name ?? 'Support';

            CustomerNotification::create([
                'client_id' => $clientId,
                'user_id' => $userId,
                'type' => 'chat',
                'title' => "New message from {$storefrontName}",
                'body' => substr($request->message, 0, 120),
                'link' => route('ecommerce.chat', ['store' => $store]),
                'icon' => 'ph-chats',
                'icon_color' => 'primary',
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to create chat notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $msg,
        ]);
    }

    /**
     * GET /ecommerce-admin/crm/api/chat/{userId}/poll?after=2026-08-20T12:00:00
     * Admin polls for new messages since a timestamp.
     */
    public function adminPoll(Request $request, int $userId)
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return response()->json(['success' => false, 'message' => 'Client not resolved'], 422);
        }

        $after = $request->query('after', now()->subHour()->toIso8601String());

        $messages = ChatMessage::forClient($clientId)
            ->forUser($userId)
            ->since($after)
            ->orderByDesc('created_at')
            ->get();

        // Mark as read
        ChatMessage::forClient($clientId)
            ->forUser($userId)
            ->unread()
            ->where('sender_type', 'customer')
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    // ================================================================
    //  STOREFRONT ENDPOINTS
    // ================================================================

    /**
     * GET /api/chat/messages
     * Customer gets their messages.
     */
    public function customerMessages(Request $request)
    {
        $clientId = $this->getClientId();
        $user = Auth::guard('ecommerce')->user();

        if (!$clientId || !$user) {
            return response()->json(['success' => false, 'data' => collect()]);
        }

        $messages = ChatMessage::forClient($clientId)
            ->forUser($user->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        // Mark admin messages as read
        ChatMessage::forClient($clientId)
            ->forUser($user->id)
            ->unread()
            ->where('sender_type', 'admin')
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * POST /api/chat/send
     * Customer sends a message to the admin.
     */
    public function customerSend(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $clientId = $this->getClientId();
        $user = Auth::guard('ecommerce')->user();

        if (!$clientId || !$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $msg = ChatMessage::create([
            'client_id' => $clientId,
            'user_id' => $user->id,
            'sender_type' => 'customer',
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $msg,
        ]);
    }

    /**
     * GET /api/chat/poll?after=2026-08-20T12:00:00
     * Customer polls for new messages since a timestamp.
     */
    public function customerPoll(Request $request)
    {
        $clientId = $this->getClientId();
        $user = Auth::guard('ecommerce')->user();

        if (!$clientId || !$user) {
            return response()->json(['success' => false, 'data' => collect()]);
        }

        $after = $request->query('after', now()->subHour()->toIso8601String());

        $messages = ChatMessage::forClient($clientId)
            ->forUser($user->id)
            ->since($after)
            ->orderByDesc('created_at')
            ->get();

        // Mark as read
        ChatMessage::forClient($clientId)
            ->forUser($user->id)
            ->unread()
            ->where('sender_type', 'admin')
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }
}
