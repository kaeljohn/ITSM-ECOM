<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('cart_id')->nullable()->index();
            $table->string('email', 255)->nullable();
            $table->decimal('cart_total', 12, 2)->default(0);
            $table->jsonb('items_summary')->nullable();
            $table->string('status', 30)->default('pending')->comment('pending, recovered, lost, notified');
            $table->timestamp('abandoned_at');
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'abandoned_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_abandoned_carts');
    }
};
