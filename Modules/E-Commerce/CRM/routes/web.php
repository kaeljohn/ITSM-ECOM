<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\CRM\Http\Controllers\CrmAdminController;
use Modules\Ecommerce\CRM\Http\Controllers\CrmApiController;

// CRM routes, scoped under the existing ecommerce admin middleware
Route::name('ecommerce.')->group(function () {
    Route::prefix('ecommerce-admin/crm')->name('admin.crm.')->middleware('ecommerce.admin')->group(function () {
        Route::get('/', [CrmAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/customers', [CrmAdminController::class, 'customers'])->name('customers');
        Route::get('/customers/{id}', [CrmAdminController::class, 'customerShow'])->name('customers.show');
        Route::put('/customers/{id}', [CrmAdminController::class, 'customerUpdate'])->name('customers.update');
        Route::get('/abandoned-carts', [CrmAdminController::class, 'abandonedCarts'])->name('abandoned-carts');
        Route::get('/tickets', [CrmAdminController::class, 'tickets'])->name('tickets');
        Route::get('/reviews', [CrmAdminController::class, 'reviews'])->name('reviews');
        Route::post('/reviews/{id}/approve', [CrmAdminController::class, 'approveReview'])->name('reviews.approve');

        Route::get('/coupons', [CrmAdminController::class, 'coupons'])->name('coupons');
        Route::get('/coupons/create', [CrmAdminController::class, 'couponForm'])->name('coupons.create');
        Route::post('/coupons', [CrmAdminController::class, 'couponSave'])->name('coupons.store');
        Route::get('/coupons/{id}/edit', [CrmAdminController::class, 'couponForm'])->name('coupons.edit');
        Route::put('/coupons/{id}', [CrmAdminController::class, 'couponSave'])->name('coupons.update');
        Route::delete('/coupons/{id}', [CrmAdminController::class, 'couponDelete'])->name('coupons.destroy');

        Route::get('/segments', [CrmAdminController::class, 'segments'])->name('segments');
        Route::get('/campaigns', [CrmAdminController::class, 'campaigns'])->name('campaigns');
        Route::get('/campaigns/{id}/events', [CrmAdminController::class, 'campaignEvents'])->name('campaigns.events');
        // Communication Templates
        Route::get('/templates', [CrmAdminController::class, 'templates'])->name('templates');
        Route::get('/templates/create', [CrmAdminController::class, 'templateForm'])->name('templates.create');
        Route::post('/templates', [CrmAdminController::class, 'templateSave'])->name('templates.store');
        Route::get('/templates/{id}/edit', [CrmAdminController::class, 'templateForm'])->name('templates.edit');
        Route::put('/templates/{id}', [CrmAdminController::class, 'templateSave'])->name('templates.update');
        Route::delete('/templates/{id}', [CrmAdminController::class, 'templateDelete'])->name('templates.destroy');
        Route::post('/templates/preview', [CrmAdminController::class, 'templatePreview'])->name('templates.preview');
        Route::post('/templates/test-send', [CrmAdminController::class, 'templateTestSend'])->name('templates.test-send');

        // Sales Pipeline — Leads
        Route::get('/leads', [CrmAdminController::class, 'leadsPipeline'])->name('leads.pipeline');
        Route::get('/leads/create', [CrmAdminController::class, 'leadForm'])->name('leads.create');
        Route::post('/leads', [CrmAdminController::class, 'leadSave'])->name('leads.store');
        Route::get('/leads/{id}', [CrmAdminController::class, 'leadShow'])->name('leads.show');
        Route::get('/leads/{id}/edit', [CrmAdminController::class, 'leadForm'])->name('leads.edit');
        Route::put('/leads/{id}', [CrmAdminController::class, 'leadSave'])->name('leads.update');
        Route::patch('/leads/{id}/status', [CrmAdminController::class, 'leadUpdateStatus'])->name('leads.update-status');
        Route::post('/leads/{id}/convert', [CrmAdminController::class, 'leadConvert'])->name('leads.convert');
        Route::delete('/leads/{id}', [CrmAdminController::class, 'leadDelete'])->name('leads.destroy');

        // Notifications
        Route::get('/notifications', [CrmAdminController::class, 'notifications'])->name('notifications');
    });
});


// ── CRM RESTful API routes ─────────────────────────────────────────────
Route::name('ecommerce.')->group(function () {
    Route::prefix('ecommerce-admin/crm/api')->name('admin.crm.api.')->middleware('ecommerce.admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [CrmApiController::class, 'dashboard'])->name('dashboard');

        // Customers 360
        Route::get('/customers', [CrmApiController::class, 'customers'])->name('customers');
        Route::get('/customers/{id}', [CrmApiController::class, 'customerShow'])->name('customers.show');
        Route::post('/customers/{id}/recalculate', [CrmApiController::class, 'customerRecalculate'])->name('customers.recalculate');
        Route::put('/customers/{id}/notes', [CrmApiController::class, 'customerUpdateNotes'])->name('customers.notes');
        Route::put('/customers/{id}/tags', [CrmApiController::class, 'customerUpdateTags'])->name('customers.tags');
        Route::put('/customers/{id}/consent', [CrmApiController::class, 'customerUpdateConsent'])->name('customers.consent');

        // Activity Timeline
        Route::get('/customers/{id}/timeline', [CrmApiController::class, 'customerTimeline'])->name('customers.timeline');
        Route::post('/customers/{id}/timeline', [CrmApiController::class, 'customerRecordTimelineEvent'])->name('customers.timeline.store');

        // Tickets
        Route::get('/tickets', [CrmApiController::class, 'tickets'])->name('tickets');
        Route::post('/tickets', [CrmApiController::class, 'ticketCreate'])->name('tickets.store');
        Route::get('/tickets/{id}', [CrmApiController::class, 'ticketShow'])->name('tickets.show');
        Route::put('/tickets/{id}', [CrmApiController::class, 'ticketUpdate'])->name('tickets.update');
        Route::get('/tickets/{id}/notes', [CrmApiController::class, 'ticketNotes'])->name('tickets.notes');
        Route::post('/tickets/{id}/notes', [CrmApiController::class, 'ticketAddNote'])->name('tickets.notes.store');

        // Segmentation & Tags
        Route::get('/segments', [CrmApiController::class, 'segments'])->name('segments');
        Route::post('/segments', [CrmApiController::class, 'segmentCreate'])->name('segments.store');
        Route::post('/segments/evaluate', [CrmApiController::class, 'segmentsEvaluate'])->name('segments.evaluate');
        Route::get('/tags', [CrmApiController::class, 'tags'])->name('tags');
        Route::post('/tags', [CrmApiController::class, 'tagCreate'])->name('tags.store');
        Route::delete('/tags/{id}', [CrmApiController::class, 'tagDelete'])->name('tags.destroy');

        
        // Orders (Pillar 3)
        Route::get('/customers/{id}/orders', [CrmApiController::class, 'customerOrders'])->name('customers.orders');

        // Segment update/delete (full CRUD)
        Route::put('/segments/{id}', [CrmApiController::class, 'segmentUpdate'])->name('segments.update');
        Route::delete('/segments/{id}', [CrmApiController::class, 'segmentDelete'])->name('segments.destroy');

        // Tag update
        Route::put('/tags/{id}', [CrmApiController::class, 'tagUpdate'])->name('tags.update');
        // Campaign Log
        Route::get('/customers/{id}/campaigns', [CrmApiController::class, 'customerCampaigns'])->name('customers.campaigns');
        Route::get('/campaigns/{id}/events', [CrmApiController::class, 'campaignEvents'])->name('campaigns.events');

        // Consent Log
        Route::get('/customers/{id}/consent', [CrmApiController::class, 'customerConsentHistory'])->name('customers.consent');

        // ── Admin Notifications ──
        Route::get('/notifications', [CrmApiController::class, 'notifications'])->name('notifications');
        Route::get('/notifications/unread', [CrmApiController::class, 'notificationsUnread'])->name('notifications.unread');
        Route::get('/notifications/sse', [CrmApiController::class, 'notificationsSse'])->name('notifications.sse');
        Route::post('/notifications/{id}/mark-read', [CrmApiController::class, 'notificationsMarkRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [CrmApiController::class, 'notificationsMarkAllRead'])->name('notifications.mark-all-read');
    });
});