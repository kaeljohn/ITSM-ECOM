<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('type', 60)->index()->comment('e.g. ticket_created, new_order, lead_converted');
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('link', 500)->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();

            $table->index(['client_id', 'is_read']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_admin_notifications');
    }
};
