<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_communications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('type', 30)->default('email')->comment('email, sms, note');
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('direction', 10)->default('outbound')->comment('inbound, outbound');
            $table->string('status', 30)->default('sent')->comment('sent, delivered, bounced, failed');
            $table->string('reference_type', 50)->nullable()->comment('order, coupon, review, etc.');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'customer_id']);
            $table->index(['client_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_communications');
    }
};
