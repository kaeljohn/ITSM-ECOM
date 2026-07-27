<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_campaign_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Customer relationship
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->cascadeOnDelete();

            // Campaign identification
            $table->string('campaign_name', 200)->nullable()
                ->comment('Readable name: "July Newsletter", "Abandoned Cart #3"');
            $table->string('campaign_type', 30)->default('email')
                ->comment('email, sms');

            // Message content preview
            $table->string('subject', 255)->nullable();
            $table->text('body_preview')->nullable()
                ->comment('First ~200 chars of the body for quick scan');

            // Direction
            $table->string('direction', 10)->default('outbound')
                ->comment('outbound, inbound');

            // Delivery status (high-level)
            $table->string('status', 30)->default('sent')->index()
                ->comment('queued, sent, delivered, bounced, failed, spam');

            // Foreign relationships
            $table->unsignedBigInteger('template_id')->nullable()
                ->comment('FK to crm_communication_templates');
            $table->unsignedBigInteger('sent_by_user_id')->nullable()
                ->comment('Staff user who triggered the send (null = automated)');

            // Provider metadata
            $table->string('provider_message_id', 255)->nullable()
                ->comment('Message ID from SES/SendGrid/Twilio for tracking');
            $table->string('provider', 50)->nullable()
                ->comment('ses, sendgrid, twilio, mailgun');

            // Timing
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('first_clicked_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['client_id', 'customer_id', 'sent_at'], 'crm_camp_cust_sent_idx');
            $table->index(['client_id', 'campaign_type', 'status', 'sent_at'], 'crm_camp_type_status_idx');
            $table->index(['client_id', 'campaign_name'], 'crm_camp_name_idx');
            $table->index(['client_id', 'provider_message_id'], 'crm_camp_provider_msg_idx');
            $table->index(['client_id', 'template_id'], 'crm_camp_template_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_campaign_log');
    }
};
