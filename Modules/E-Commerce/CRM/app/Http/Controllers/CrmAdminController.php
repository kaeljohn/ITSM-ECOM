<?php

namespace Modules\Ecommerce\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Models\AbandonedCart;
use Modules\Ecommerce\CRM\Models\ProductReview;
use Modules\Ecommerce\CRM\Models\Coupon;
use Modules\Ecommerce\CRM\Models\Lead;
use Modules\Ecommerce\CRM\Models\Segment;
use Modules\Ecommerce\CRM\Services\CrmDashboardService;
use Illuminate\Support\Str;

class CrmAdminController extends Controller
{
    public function __construct(
        protected CrmDashboardService $dashboardService
    ) {}

    /**
     * CRM Dashboard — KPIs and overview.
     */
    public function dashboard()
    {
        $data = $this->dashboardService->overview();

        return view('crm::admin.dashboard', $data);
    }

    /**
     * Customers list — searchable, filterable.
     */
    public function customers(Request $request)
    {
        $query = Customer::query()
            ->with(['tags', 'segments'])
            ->orderByDesc('last_purchase_at')
            ->orderByDesc('id');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        // Segment filter
        if ($segmentId = $request->input('segment_id')) {
            $query->whereHas('segments', fn ($q) => $q->where('crm_segments.id', $segmentId));
        }

        // Source filter
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $customers = $query->paginate(25)->withQueryString();
        $segments = \Modules\Ecommerce\CRM\Models\Segment::orderBy('name')->get(['id', 'name']);

        return view('crm::admin.customers', compact('customers', 'segments'));
    }

    /**
     * Customer 360° detail view.
     */
    public function customerShow($id)
    {
        $customer = Customer::with(['tags', 'segments', 'communications' => fn ($q) => $q->latest()])
            ->findOrFail($id);

        $reviews = ProductReview::where('user_id', $customer->user_id)
            ->latest()
            ->take(10)
            ->get();

        return view('crm::admin.customer-show', compact('customer', 'reviews'));
    }

    /**
     * Update customer notes / tags (quick edit).
     */
    public function customerUpdate(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:crm_tags,id',
        ]);

        if (array_key_exists('notes', $validated)) {
            $customer->update(['notes' => $validated['notes']]);
        }

        if (array_key_exists('tags', $validated)) {
            $customer->tags()->sync($validated['tags']);
        }

        return back()->with('success', 'Customer updated.');
    }

    /**
     * Abandoned carts list.
     */
    public function abandonedCarts(Request $request)
    {
        $query = AbandonedCart::with(['customer' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('abandoned_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $carts = $query->paginate(25)->withQueryString();

        return view('crm::admin.abandoned-carts', compact('carts'));
    }

    /**
     * Product reviews list (approval workflow).
     */
    public function reviews(Request $request)
    {
        $query = ProductReview::with(['customer' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('created_at');

        if ($request->boolean('pending')) {
            $query->where('approved', false);
        }

        $reviews = $query->paginate(25)->withQueryString();

        return view('crm::admin.reviews', compact('reviews'));
    }

    /**
     * Approve a product review.
     */
    public function approveReview($id)
    {
        $review = ProductReview::findOrFail($id);
        $review->update([
            'approved' => true,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Review approved and live on the storefront.');
    }

    // ============================================================
    // COUPON MANAGEMENT
    // ============================================================

    /**
     * Coupons list.
     */
    public function coupons(Request $request)
    {
        $query = Coupon::withCount('redemptions')->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where('code', 'ilike', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $coupons = $query->paginate(25)->withQueryString();

        return view('crm::admin.coupons', compact('coupons'));
    }

    /**
     * Show coupon create/edit form.
     */
    public function couponForm(Request $request, $id = null)
    {
        $coupon = $id ? Coupon::findOrFail($id) : new Coupon();
        $segments = Segment::orderBy('name')->get(['id', 'name']);

        return view('crm::admin.coupon-form', compact('coupon', 'segments'));
    }

    /**
     * Create or update a coupon.
     */
    public function couponSave(Request $request, $id = null)
    {
        $rules = [
            'code' => 'required|string|max:50',
            'type' => 'required|in:fixed,percentage,free_shipping',
            'value' => 'required_if:type,fixed,percentage|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'segment_id' => 'nullable|integer|exists:crm_segments,id',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'description' => 'nullable|string|max:1000',
        ];

        $validated = $request->validate($rules);

        $coupon = $id ? Coupon::findOrFail($id) : new Coupon();

        // Auto-uppercase the code
        $validated['code'] = strtoupper($validated['code']);

        // If free_shipping, set value to 0
        if ($validated['type'] === 'free_shipping') {
            $validated['value'] = 0;
        }

        $coupon->fill($validated);
        $coupon->save();

        return redirect()->route('ecommerce.admin.crm.coupons')
            ->with('success', $id ? 'Coupon updated.' : 'Coupon created.');
    }

    /**
     * Delete a coupon.
     */
    public function couponDelete($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }

    // ============================================================
    // COMMUNICATION TEMPLATES
    // ============================================================

    /**
     * Communication templates list.
     */
    public function templates(Request $request)
    {
        $templates = \Modules\Ecommerce\CRM\Models\CommunicationTemplate::orderByDesc('created_at')
            ->paginate(25);

        return view('crm::admin.templates', compact('templates'));
    }

    // ============================================================
    // SALES PIPELINE — LEADS & OPPORTUNITIES
    // ============================================================

    /**
     * Sales pipeline board — Kanban view grouped by status.
     */
    public function leadsPipeline(Request $request)
    {
        $statuses = Lead::PIPELINE_STATUSES;
        $closedStatuses = Lead::CLOSED_STATUSES;

        $leadsByStatus = [];
        foreach (array_merge($statuses, $closedStatuses) as $status) {
            $query = Lead::byStatus($status)->orderByDesc('expected_value')->orderByDesc('updated_at');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhere('company_name', 'ilike', "%{$search}%");
                });
            }

            if ($source = $request->input('source')) {
                $query->where('source', $source);
            }

            if ($assigned = $request->input('assigned_to')) {
                $query->where('assigned_to', $assigned);
            }

            $leadsByStatus[$status] = $query->get();
        }

        $sources = Lead::select('source', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $assignedReps = Lead::select('assigned_to')
            ->whereNotNull('assigned_to')
            ->distinct()
            ->orderBy('assigned_to')
            ->pluck('assigned_to');

        // Quick KPIs
        $pipelineValue = Lead::inPipeline()->sum('expected_value');
        $wonCount = Lead::won()->count();
        $lostCount = Lead::lost()->count();
        $totalClosed = $wonCount + $lostCount;
        $winRate = $totalClosed > 0 ? round($wonCount / $totalClosed * 100) : 0;
        $totalLeads = Lead::count();

        return view('crm::admin.leads-pipeline', compact(
            'leadsByStatus', 'statuses', 'closedStatuses',
            'sources', 'assignedReps',
            'pipelineValue', 'wonCount', 'lostCount', 'winRate', 'totalLeads',
        ));
    }

    /**
     * Show lead create/edit form.
     */
    public function leadForm(Request $request, $id = null)
    {
        $lead = $id ? Lead::with('customer')->findOrFail($id) : new Lead();
        $customers = Customer::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('crm::admin.lead-form', compact('lead', 'customers'));
    }

    /**
     * Create or update a lead.
     */
    public function leadSave(Request $request, $id = null)
    {
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:200',
            'job_title' => 'nullable|string|max:200',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'source' => 'nullable|string|max:50',
            'expected_value' => 'nullable|numeric|min:0',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:200',
            'customer_id' => 'nullable|integer|exists:crm_customers,id',
            'notes' => 'nullable|string|max:5000',
        ];

        $validated = $request->validate($rules);

        $lead = $id ? Lead::findOrFail($id) : new Lead();
        $oldStatus = $lead->status;

        $lead->fill($validated);
        $lead->save();

        // Log activity on status change
        if ($id && $oldStatus !== $lead->status) {
            $lead->logActivity('status_change', "Status changed from " . ucfirst($oldStatus) . " to " . ucfirst($lead->status));
        }

        // If won and no customer linked, try to find matching customer
        if ($lead->status === 'won' && !$lead->customer_id) {
            $customer = Customer::where('email', $lead->email)->first();
            if ($customer) {
                $lead->update([
                    'customer_id' => $customer->id,
                    'converted_at' => $lead->converted_at ?? now(),
                    'actual_value' => $lead->actual_value ?: $lead->expected_value,
                ]);
                $lead->logActivity('converted', 'Lead converted to customer automatically.');
            }
        }

        return redirect()->route('ecommerce.admin.crm.leads.pipeline')
            ->with('success', $id ? 'Lead updated.' : 'Lead created.');
    }

    /**
     * Quick update lead status (used by Kanban drag or inline actions).
     */
    public function leadUpdateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
        ]);

        $lead = Lead::findOrFail($id);
        $oldStatus = $lead->status;
        $lead->update(['status' => $validated['status']]);

        $lead->logActivity('status_change', "Status changed from " . ucfirst($oldStatus) . " to " . ucfirst($lead->status));

        // If moved to won
        if ($validated['status'] === 'won' && !$lead->customer_id) {
            $customer = Customer::where('email', $lead->email)->first();
            if ($customer) {
                $lead->update([
                    'customer_id' => $customer->id,
                    'converted_at' => now(),
                    'actual_value' => $lead->actual_value ?: $lead->expected_value,
                ]);
                $lead->logActivity('converted', 'Lead converted to customer on win.');
            }
        }

        return back()->with('success', "Lead moved to " . ucfirst($validated['status']) . ".");
    }

    /**
     * Convert a lead to a CRM customer.
     */
    public function leadConvert($id)
    {
        $lead = Lead::findOrFail($id);

        if ($lead->customer_id) {
            return back()->with('success', 'This lead is already linked to a customer.');
        }

        $customer = Customer::where('email', $lead->email)->first();

        if (!$customer) {
            $customer = Customer::create([
                'email' => $lead->email,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'phone' => $lead->phone,
                'source' => $lead->source ?: 'lead',
            ]);
        }

        $lead->update([
            'customer_id' => $customer->id,
            'converted_at' => now(),
            'status' => 'won',
            'actual_value' => $lead->actual_value ?: $lead->expected_value,
        ]);

        $lead->logActivity('converted', 'Lead converted to customer manually.');

        return redirect()->route('ecommerce.admin.crm.customers.show', $customer->id)
            ->with('success', 'Lead converted to customer successfully.');
    }

    /**
     * Lead detail view with full timeline.
     */
    public function leadShow($id)
    {
        $lead = Lead::with(['customer', 'communications' => fn ($q) => $q->latest()])
            ->findOrFail($id);

        return view('crm::admin.lead-show', compact('lead'));
    }

    /**
     * Delete a lead.
     */
    public function leadDelete($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        return back()->with('success', 'Lead deleted.');
    }
}
