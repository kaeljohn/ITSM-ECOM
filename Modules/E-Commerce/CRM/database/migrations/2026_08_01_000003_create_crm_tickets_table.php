<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Customer relationship
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->cascadeOnDelete();

            // Core fields
            $table->string('subject', 255);
            $table->text('description')->nullable();

            // Status pipeline: open → pending → resolved → closed
            $table->string('status', 30)->default('open')->index()
                ->comment('open, pending, resolved, closed');

            // Priority levels
            $table->string('priority', 20)->default('normal')->index()
                ->comment('low, normal, high, urgent');

            // Channel where the ticket originated
            $table->string('channel', 30)->default('email')
                ->comment('email, chat, phone, portal, social');

            // Assignment
            $table->string('assigned_to', 200)->nullable()
                ->comment('Staff name/identifier for display');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable()
                ->comment('FK to the staff user (ecommerce_admin or ERP user)');

            // Optional link to a related order
            // Both default (pgsql) and ecommerce connections point to the same NeonDB,
            // so cross-connection FK is not an issue.
            $table->uuid('order_id')->nullable()->index();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();

            // Category / department routing
            $table->string('category', 100)->nullable()
                ->comment('billing, shipping, product, account, other');

            // Resolution tracking
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['client_id', 'status', 'priority'], 'crm_tkt_status_prio_idx');
            $table->index(['client_id', 'customer_id'], 'crm_tkt_customer_idx');
            $table->index(['client_id', 'assigned_to'], 'crm_tkt_assignee_idx');
            $table->index(['client_id', 'category'], 'crm_tkt_category_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_tickets');
    }
};
