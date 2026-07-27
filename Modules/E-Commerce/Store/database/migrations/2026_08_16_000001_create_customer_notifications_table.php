<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('null = broadcast to all customers');
            $table->string('type', 60)->default('general')->index();
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('link', 500)->nullable();
            $table->string('icon', 60)->nullable()->default('ph-megaphone');
            $table->string('icon_color', 20)->nullable()->default('blue');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_read']);
            $table->index(['client_id', 'user_id']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('customer_notifications');
    }
};
