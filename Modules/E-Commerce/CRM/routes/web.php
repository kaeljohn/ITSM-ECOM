<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\CRM\Http\Controllers\CrmAdminController;

// CRM routes, scoped under the existing ecommerce admin middleware
Route::name('ecommerce.')->group(function () {
    Route::prefix('ecommerce-admin/crm')->name('admin.crm.')->middleware('ecommerce.admin')->group(function () {
        Route::get('/', [CrmAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/customers', [CrmAdminController::class, 'customers'])->name('customers');
        Route::get('/customers/{id}', [CrmAdminController::class, 'customerShow'])->name('customers.show');
        Route::put('/customers/{id}', [CrmAdminController::class, 'customerUpdate'])->name('customers.update');
        Route::get('/abandoned-carts', [CrmAdminController::class, 'abandonedCarts'])->name('abandoned-carts');
        Route::get('/reviews', [CrmAdminController::class, 'reviews'])->name('reviews');
        Route::post('/reviews/{id}/approve', [CrmAdminController::class, 'approveReview'])->name('reviews.approve');

        Route::get('/coupons', [CrmAdminController::class, 'coupons'])->name('coupons');
        Route::get('/coupons/create', [CrmAdminController::class, 'couponForm'])->name('coupons.create');
        Route::post('/coupons', [CrmAdminController::class, 'couponSave'])->name('coupons.store');
        Route::get('/coupons/{id}/edit', [CrmAdminController::class, 'couponForm'])->name('coupons.edit');
        Route::put('/coupons/{id}', [CrmAdminController::class, 'couponSave'])->name('coupons.update');
        Route::delete('/coupons/{id}', [CrmAdminController::class, 'couponDelete'])->name('coupons.destroy');

        Route::get('/templates', [CrmAdminController::class, 'templates'])->name('templates');

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
    });
});
