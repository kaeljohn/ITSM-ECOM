<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Core relationship
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->cascadeOnDelete();

            // Event classification
            $table->string('type', 50)->comment('order, ticket, note, campaign, review, lead, system');
            $table->string('action', 100)->comment('created, updated, resolved, sent, opened, clicked, etc.');

            // Human-readable summary (rendered in timeline)
            $table->string('summary', 500)->nullable();

            // Polymorphic reference to the source entity
            $table->nullableMorphs('reference', 'crm_act_ref_idx'); // reference_type + reference_id

            // Flexible metadata payload
            $table->jsonb('metadata')->nullable()->comment('Arbitrary payload: order totals, ticket priority, campaign ID, etc.');

            // When the event OCCURRED (distinct from created_at for backfill)
            $table->timestamp('occurred_at')->index();

            $table->timestamps();

            // Performance indexes
            $table->index(['client_id', 'customer_id', 'occurred_at'], 'crm_act_cust_occ_idx');
            $table->index(['client_id', 'type', 'occurred_at'], 'crm_act_type_occ_idx');
            $table->index(['client_id', 'reference_type', 'reference_id'], 'crm_act_ref_search_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_activity_log');
    }
};
