<?php

namespace Modules\Ecommerce\CRM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\CRM\Models\CampaignLog;
use Modules\Ecommerce\CRM\Models\ConsentLog;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\Segment;
use Modules\Ecommerce\CRM\Models\Tag;
use Modules\Ecommerce\CRM\Models\Ticket;
use Modules\Ecommerce\CRM\Models\TicketNote;
use Modules\Ecommerce\CRM\Services\ActivityTimelineService;
use Modules\Ecommerce\CRM\Services\ChurnRiskService;
use Modules\Ecommerce\CRM\Services\CrmDashboardService;
use Modules\Ecommerce\CRM\Services\LtvCalculator;
use Modules\Ecommerce\CRM\Services\NotificationService;
use Modules\Ecommerce\CRM\Services\RfmSegmentEngine;

class CrmApiController extends Controller
{
    public function __construct(
        protected CrmDashboardService $dashboardService,
        protected LtvCalculator $ltvCalculator,
        protected ChurnRiskService $churnRiskService,
        protected ActivityTimelineService $timelineService,
        protected RfmSegmentEngine $rfmEngine,
    ) {}

    // Dashboards
    public function dashboard(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->dashboardService->overview()]);
    }

    // Customers 360
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::query()->with(['tags', 'segments'])->orderByDesc('last_purchase_at')->orderByDesc('id');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")->orWhere('last_name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%")->orWhere('phone', 'ilike', "%{$search}%");
            });
        }
        if ($request->input('segment_id')) { $query->whereHas('segments', fn ($q) => $q->where('crm_segments.id', $request->input('segment_id'))); }
        if ($request->input('source')) { $query->where('source', $request->input('source')); }
        if ($request->input('churn_risk')) { $query->where('churn_risk', $request->input('churn_risk')); }
        if ($request->input('tier')) { $query->where('tier', $request->input('tier')); }
        $customers = $query->paginate(min((int) $request->input('per_page', 25), 100));
        return response()->json(['success' => true, 'data' => $customers->items(), 'meta' => ['current_page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'per_page' => $customers->perPage(), 'total' => $customers->total()]]);
    }

    public function customerShow(int $id): JsonResponse
    {
        $customer = Customer::with(['tags', 'segments', 'communications' => fn ($q) => $q->latest()->take(20), 'reviews' => fn ($q) => $q->latest()->take(10), 'tickets' => fn ($q) => $q->latest()->take(10), 'campaignLogs' => fn ($q) => $q->latest()->take(10), 'consentLogs' => fn ($q) => $q->latest()->take(10)])->findOrFail($id);
        return response()->json(['success' => true, 'data' => ['customer' => $customer, 'projected_ltv' => $this->ltvCalculator->projectedLtv($customer), 'months_since_purchase' => $this->ltvCalculator->monthsSinceLastPurchase($customer), 'open_ticket_count' => $customer->tickets()->whereIn('status', ['open', 'pending'])->count()]]);
    }

    public function customerRecalculate(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        try { $this->ltvCalculator->computeForCustomer($customer); $this->churnRiskService->evaluateForCustomer($customer); $rfm = $this->rfmEngine->scoreCustomer($customer); } catch (\Throwable $e) { return response()->json(['success' => false, 'message' => $e->getMessage()], 500); }
        $customer->refresh();
        return response()->json(['success' => true, 'message' => 'Recalculated.', 'data' => ['customer' => $customer, 'rfm' => $rfm, 'projected_ltv' => $this->ltvCalculator->projectedLtv($customer)]]);
    }

    public function customerUpdateNotes(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['notes' => 'nullable|string|max:10000']); $c = Customer::findOrFail($id); $c->update(['notes' => $v['notes'] ?? null]);
        return response()->json(['success' => true, 'message' => 'Notes updated.', 'data' => ['notes' => $c->notes]]);
    }

    public function customerUpdateTags(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['tags' => 'required|array', 'tags.*' => 'integer|exists:ecommerce.crm_tags,id']); $c = Customer::findOrFail($id); $c->tags()->sync($v['tags']);
        return response()->json(['success' => true, 'message' => 'Tags updated.', 'data' => ['tag_ids' => $v['tags']]]);
    }

    public function customerUpdateConsent(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['opt_in_email' => 'required|boolean', 'opt_in_sms' => 'required|boolean']); $c = Customer::findOrFail($id);
        $c->update(['opt_in_email' => $v['opt_in_email'], 'opt_in_sms' => $v['opt_in_sms'], 'opted_in_at' => now()]);
        foreach (['email' => 'opt_in_email', 'sms' => 'opt_in_sms'] as $ch => $f) { ConsentLog::create(['customer_id' => $c->id, 'channel' => $ch, 'action' => $v[$f] ? 'opt_in' : 'opt_out', 'source' => 'api', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'occurred_at' => now()]); }
        $this->timelineService->recordEvent(customerId: $c->id, type: 'system', action: 'consent_updated', summary: ($v["opt_in_email"] ? "email " : "-email ") . ($v["opt_in_sms"] ? "sms" : "-sms"));
        return response()->json(['success' => true, 'message' => 'Consent updated.', 'data' => ['opt_in_email' => $c->opt_in_email, 'opt_in_sms' => $c->opt_in_sms]]);
    }

    // Orders
    public function customerOrders(int $id, Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        if (!$customer->user_id) { return response()->json(['success' => true, 'data' => [], 'meta' => ['total' => 0]]); }
        $orders = \Modules\Ecommerce\Models\Order::where('user_id', $customer->user_id)->with('items')->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 25), 100));
        $data = $orders->map(function ($o) { return ['id' => $o->id, 'status' => $o->status, 'total' => (float) $o->total, 'shipping_fee' => (float) $o->shipping_fee, 'payment_method' => $o->payment_method, 'payment_status' => $o->payment_status, 'tracking_number' => $o->tracking_number, 'item_count' => $o->items->sum('quantity'), 'items' => $o->items->map(fn ($i) => ['name' => $i->name, 'price' => (float) $i->price, 'quantity' => $i->quantity, 'product_type' => $i->product_type]), 'created_at' => $o->created_at->toIso8601String()]; });
        return response()->json(['success' => true, 'data' => $data, 'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total()]]);
    }

    // Timeline
    public function customerTimeline(Request $request, int $id): JsonResponse
    {
        $request->validate(['types' => 'nullable|array', 'types.*' => 'string|in:order,ticket,note,campaign,review,activity', 'limit' => 'nullable|integer|min:1|max:200', 'before' => 'nullable|date']);
        $events = $this->timelineService->buildForCustomer(customerId: $id, types: $request->input('types'), limit: (int) ($request->input('limit', 50)), before: $request->input('before'));
        return response()->json(['success' => true, 'data' => $events->values(), 'meta' => ['count' => $events->count(), 'next_cursor' => $events->isNotEmpty() ? $events->last()['occurred_at'] : null]]);
    }

    public function customerRecordTimelineEvent(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['type' => 'required|string|max:50', 'action' => 'required|string|max:100', 'summary' => 'nullable|string|max:500', 'metadata' => 'nullable|array', 'occurred_at' => 'nullable|date']);
        Customer::findOrFail($id); $log = $this->timelineService->recordEvent(customerId: $id, type: $v['type'], action: $v['action'], summary: $v['summary'] ?? null, metadata: $v['metadata'] ?? null, occurredAt: $v['occurred_at'] ?? now());
        return response()->json(['success' => true, 'message' => 'Event recorded.', 'data' => $log], 201);
    }

    // Tickets
    public function tickets(Request $request): JsonResponse
    {
        $query = Ticket::with(['customer' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email')])->urgentFirst()->newestFirst();
        if ($s = $request->input('status')) { $query->where('status', $s); } if ($p = $request->input('priority')) { $query->where('priority', $p); } if ($c = $request->input('category')) { $query->where('category', $c); } if ($a = $request->input('assigned_to')) { $query->where('assigned_to_user_id', $a); } if ($request->boolean('unassigned')) { $query->whereNull('assigned_to_user_id'); } if ($cid = $request->input('customer_id')) { $query->where('customer_id', $cid); }
        $tickets = $query->paginate(min((int) $request->input('per_page', 25), 100));
        return response()->json(['success' => true, 'data' => $tickets->items(), 'meta' => ['current_page' => $tickets->currentPage(), 'last_page' => $tickets->lastPage(), 'per_page' => $tickets->perPage(), 'total' => $tickets->total()]]);
    }

    public function ticketCreate(Request $request): JsonResponse
    {
        $v = $request->validate(['customer_id' => 'required|integer|exists:ecommerce.crm_customers,id', 'subject' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'priority' => 'nullable|string|in:low,normal,high,urgent', 'channel' => 'nullable|string|in:email,chat,phone,portal,social', 'category' => 'nullable|string|max:100', 'assigned_to' => 'nullable|string|max:200', 'assigned_to_user_id' => 'nullable|integer', 'order_id' => 'nullable|string']);
        $v['status'] = 'open'; $ticket = Ticket::create($v);
        $this->timelineService->recordEvent(customerId: $ticket->customer_id, type: 'ticket', action: 'created', summary: "Ticket: {$ticket->subject}", reference: $ticket);
        // Fire notification
        $clientId = $this->getCompany()?->id;
        if ($clientId) {
            try { app(NotificationService::class)->ticketCreated($clientId, $ticket->id, $ticket->subject); } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to fire ticket_created notification: ' . $e->getMessage());
            }
        }
        return response()->json(['success' => true, 'message' => 'Ticket created.', 'data' => $ticket->load('customer')], 201);
    }

    public function ticketShow(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Ticket::with(['customer', 'notes' => fn ($q) => $q->latest()])->findOrFail($id)]);
    }

    public function ticketUpdate(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['status' => 'nullable|string|in:open,pending,resolved,closed', 'priority' => 'nullable|string|in:low,normal,high,urgent', 'assigned_to' => 'nullable|string|max:200', 'assigned_to_user_id' => 'nullable|integer', 'category' => 'nullable|string|max:100']);
        $ticket = Ticket::findOrFail($id); $oldStatus = $ticket->status;
        if (isset($v['status'])) { if ($v['status'] === 'resolved' && !$ticket->resolved_at) { $v['resolved_at'] = now(); } if ($v['status'] === 'closed') { $v['closed_at'] = now(); } }
        $ticket->update($v);
        if (isset($v['status']) && $v['status'] !== $oldStatus) { 
            $this->timelineService->recordEvent(customerId: $ticket->customer_id, type: 'ticket', action: $v['status'], summary: "Ticket #{$ticket->id}: {$oldStatus} -> {$v['status']}", reference: $ticket);
            // Fire notification
            $clientId = $this->getCompany()?->id;
            if ($clientId) {
                try { app(NotificationService::class)->ticketStatusChanged($clientId, $ticket->id, $ticket->subject, $v['status']); } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to fire ticket_status_changed notification: ' . $e->getMessage());
                }
            }
        }
        return response()->json(['success' => true, 'message' => 'Ticket updated.', 'data' => $ticket]);
    }

    public function ticketNotes(int $id): JsonResponse
    {
        Ticket::findOrFail($id);
        return response()->json(['success' => true, 'data' => TicketNote::where('ticket_id', $id)->latest()->get()]);
    }

    public function ticketAddNote(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['body' => 'required|string|max:50000', 'is_internal' => 'nullable|boolean', 'author_name' => 'nullable|string|max:150', 'author_id' => 'nullable|integer', 'note_type' => 'nullable|string|in:note,reply,internal_note,system_action', 'mentions' => 'nullable|array']);
        $ticket = Ticket::findOrFail($id); $note = $ticket->notes()->create(['author_id' => $v['author_id'] ?? null, 'author_name' => $v['author_name'] ?? 'System', 'body' => $v['body'], 'is_internal' => $v['is_internal'] ?? true, 'note_type' => $v['note_type'] ?? 'note', 'mentions' => $v['mentions'] ?? null]);
        $this->timelineService->recordEvent(customerId: $ticket->customer_id, type: 'note', action: $note->is_internal ? 'internal_note' : 'reply', summary: $note->excerpt(120), reference: $note);
        return response()->json(['success' => true, 'message' => 'Note added.', 'data' => $note], 201);
    }

    // Segments
    public function segments(Request $request): JsonResponse
    {
        $q = Segment::withCount('customers')->orderBy('name'); if ($request->boolean('auto_only')) { $q->where('is_auto', true); }
        return response()->json(['success' => true, 'data' => $q->get()]);
    }

    public function segmentCreate(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:150', 'slug' => 'required|string|max:150', 'description' => 'nullable|string|max:500', 'criteria' => 'nullable|array', 'is_auto' => 'nullable|boolean']);
        return response()->json(['success' => true, 'message' => 'Segment created.', 'data' => Segment::create($v)], 201);
    }

    public function segmentUpdate(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['name' => 'sometimes|string|max:150', 'description' => 'nullable|string|max:500', 'criteria' => 'nullable|array', 'is_auto' => 'nullable|boolean']);
        $s = Segment::findOrFail($id); $s->update($v);
        return response()->json(['success' => true, 'message' => 'Segment updated.', 'data' => $s]);
    }

    public function segmentDelete(int $id): JsonResponse
    {
        $s = Segment::findOrFail($id); $s->customers()->detach(); $s->delete();
        return response()->json(['success' => true, 'message' => 'Segment deleted.']);
    }

    public function segmentsEvaluate(Request $request): JsonResponse
    {
        $scoreResults = $this->rfmEngine->batchScoreAll($request->input('client_id')); $segmentResults = $this->rfmEngine->evaluateAutoSegments($request->input("client_id"));
        return response()->json(['success' => true, 'message' => 'Evaluation complete.', 'data' => ['customers_scored' => $scoreResults['processed'], 'errors' => $scoreResults['errors'], 'segment_distribution' => $scoreResults['segment_counts'], 'auto_segments_updated' => $segmentResults['segments_evaluated'], 'customers_assigned' => $segmentResults['customers_assigned']]]);
    }

    // Tags
    public function tags(): JsonResponse { return response()->json(['success' => true, 'data' => Tag::withCount('customers')->orderBy('name')->get()]); }

    public function tagCreate(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:100', 'color' => 'nullable|string|max:20']);
        return response()->json(['success' => true, 'message' => 'Tag created.', 'data' => Tag::create(['name' => $v['name'], 'color' => $v['color'] ?? '#6B7280'])], 201);
    }

    public function tagUpdate(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:100', 'color' => 'nullable|string|max:20']); $t = Tag::findOrFail($id); $t->update($v);
        return response()->json(['success' => true, 'message' => 'Tag updated.', 'data' => $t]);
    }

    public function tagDelete(int $id): JsonResponse { Tag::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Tag deleted.']); }

    // Campaigns
    public function customerCampaigns(int $id, Request $request): JsonResponse
    {
        $campaigns = CampaignLog::where('customer_id', $id)->with('events')->orderByDesc('sent_at')->paginate(min((int) $request->input('per_page', 25), 100));
        return response()->json(['success' => true, 'data' => $campaigns->items(), 'meta' => ['current_page' => $campaigns->currentPage(), 'last_page' => $campaigns->lastPage(), 'per_page' => $campaigns->perPage(), 'total' => $campaigns->total()]]);
    }

    public function campaignEvents(int $id): JsonResponse { return response()->json(['success' => true, 'data' => CampaignLog::findOrFail($id)->events()->orderByDesc('occurred_at')->get()]); }

    // Consent
    public function customerConsentHistory(int $id): JsonResponse { return response()->json(['success' => true, 'data' => ConsentLog::where('customer_id', $id)->orderByDesc('occurred_at')->get()]); }

    // ────────────────────────────────────────────────────────────────
    // NOTIFICATIONS
    // ────────────────────────────────────────────────────────────────

    public function notifications(Request $request): JsonResponse
    {
        $company = $this->getCompany();
        if (!$company) { return response()->json(['success' => false, 'message' => 'No company context.'], 400); }

        $limit = min((int) $request->input('limit', 25), 100);
        $notifications = app(NotificationService::class)->getRecent($company->id, $limit);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function notificationsUnread(): JsonResponse
    {
        $company = $this->getCompany();
        if (!$company) { return response()->json(['success' => false, 'message' => 'No company context.'], 400); }

        $notifications = app(NotificationService::class)->getUnread($company->id);
        $count = app(NotificationService::class)->getUnreadCount($company->id);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'notifications' => $notifications,
            ],
        ]);
    }

    public function notificationsMarkRead(int $id): JsonResponse
    {
        app(NotificationService::class)->markAsRead($id);
        return response()->json(['success' => true, 'message' => 'Marked as read.']);
    }

    public function notificationsMarkAllRead(): JsonResponse
    {
        $company = $this->getCompany();
        if (!$company) { return response()->json(['success' => false, 'message' => 'No company context.'], 400); }

        app(NotificationService::class)->markAllAsRead($company->id);
        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    /**
     * SSE stream for real-time unread notification count.
     * The browser keeps this connection open and receives events when new notifications arrive.
     */
    public function notificationsSse(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = $this->getCompany();
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company context.'], 400);
        }

        $clientId = $company->id;
        $lastCount = app(NotificationService::class)->getUnreadCount($clientId);

        // Send initial count immediately
        $initialPayload = json_encode([
            'type' => 'count',
            'count' => $lastCount,
            'notifications' => app(NotificationService::class)->getUnread($clientId),
        ]);

        return response()->stream(function () use ($clientId, &$lastCount) {
            // Set unlimited execution time
            set_time_limit(0);

            // Send initial event
            $payload = $this->getSsePayload($clientId, $lastCount);
            if ($payload) {
                echo "event: notification\n";
                echo "data: {$payload}\n\n";
                ob_flush();
                flush();
            }

            // Keep the connection open and check every 3 seconds
            $checkCount = 0;
            while (true) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                sleep(3);
                $checkCount++;

                try {
                    $payload = $this->getSsePayload($clientId, $lastCount);
                    if ($payload) {
                        echo "event: notification\n";
                        echo "data: {$payload}\n\n";
                        ob_flush();
                        flush();
                    }
                } catch (\Throwable $e) {
                    // DB error — send error event but keep the stream alive
                    $errorPayload = json_encode(['type' => 'error', 'message' => 'Server error, retrying...']);
                    echo "event: error\n";
                    echo "data: {$errorPayload}\n\n";
                    ob_flush();
                    flush();
                }

                // Send a keepalive comment every 30 seconds to prevent proxy timeouts
                if ($checkCount % 10 === 0) {
                    echo ": keepalive\n\n";
                    ob_flush();
                    flush();
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Build SSE payload if the notification count changed since last check.
     */
    private function getSsePayload(int $clientId, int &$lastCount): ?string
    {
        $service = app(NotificationService::class);
        $currentCount = $service->getUnreadCount($clientId);

        if ($currentCount === $lastCount) {
            return null;
        }

        $lastCount = $currentCount;

        $payload = json_encode([
            'type' => 'count',
            'count' => $currentCount,
            'notifications' => $service->getUnread($clientId),
        ]);

        return $payload ?: null;
    }

    /**
     * Helper: get the current company from the authenticated admin.
     */
    private function getCompany(): ?\App\Models\Company
    {
        $admin = auth('ecommerce_admin')->user();
        return $admin?->getCompany();
    }
}
