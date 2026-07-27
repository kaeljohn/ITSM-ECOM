<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_campaign_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Link back to the campaign log entry
            $table->unsignedBigInteger('campaign_log_id')->index();
            $table->foreign('campaign_log_id', 'crm_camp_evt_log_fk')
                ->references('id')
                ->on('crm_campaign_log')
                ->cascadeOnDelete();

            // Event type
            $table->string('event_type', 30)->index()
                ->comment('delivered, opened, clicked, bounced, complained, unsubscribed, failed');

            // Event payload
            $table->text('payload')->nullable()
                ->comment('URL clicked, bounce error code, complaint feedback type, etc.');

            // Device / context
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 30)->nullable()
                ->comment('desktop, mobile, tablet, unknown');

            // Geo-location (if available from provider)
            $table->string('country', 100)->nullable();
            $table->string('city', 150)->nullable();

            // When the event occurred (as reported by provider)
            $table->timestamp('occurred_at');

            $table->timestamps();

            // Performance indexes
            $table->index(['client_id', 'campaign_log_id', 'event_type'], 'crm_camp_evt_log_type_idx');
            $table->index(['client_id', 'event_type', 'occurred_at'], 'crm_camp_evt_type_occ_idx');
            $table->index(['client_id', 'ip_address'], 'crm_camp_evt_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_campaign_events');
    }
};
