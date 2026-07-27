<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_consent_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Customer relationship
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->cascadeOnDelete();

            // What channel the consent applies to
            $table->string('channel', 30)->comment('email, sms, phone, push');

            // What happened
            $table->string('action', 20)->comment('opt_in, opt_out');

            // Source/origin of the action
            $table->string('source', 50)->comment('registration, profile_update, campaign_link, manual, api, checkout');

            // Audit trail
            $table->text('notes')->nullable()
                ->comment('Reason for change: "User unsubscribed via email footer link"');

            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Who performed the change (null = customer self-service)
            $table->unsignedBigInteger('changed_by_user_id')->nullable()
                ->comment('Staff user who manually changed consent');

            // Timestamp
            $table->timestamp('occurred_at');

            $table->timestamps();

            // Performance indexes
            $table->index(['client_id', 'customer_id', 'channel'], 'crm_consent_cust_channel_idx');
            $table->index(['client_id', 'action', 'occurred_at'], 'crm_consent_action_occ_idx');
            $table->index(['client_id', 'source'], 'crm_consent_source_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_consent_log');
    }
};
