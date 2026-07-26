<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_communication_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('type', 30)->default('email')->comment('email, sms');
            $table->jsonb('variables')->nullable()->comment('Available template variables');
            $table->string('trigger_event', 50)->nullable()->comment('abandoned_cart, welcome, post_purchase');
            $table->string('status', 20)->default('draft')->comment('draft, active, archived');
            $table->timestamps();

            $table->index(['client_id', 'trigger_event']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_communication_templates');
    }
};
