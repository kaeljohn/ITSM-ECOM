<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('code', 50)->index();
            $table->string('type', 20)->default('fixed')->comment('fixed, percentage, free_shipping');
            $table->decimal('value', 10, 2)->default(0)->comment('Fixed amount or percentage value');
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable()->comment('Max discount for percentage coupons');
            $table->unsignedInteger('max_uses')->nullable()->comment('Total max redemptions');
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedBigInteger('segment_id')->nullable()->comment('Restrict to segment');
            $table->string('status', 20)->default('active')->comment('active, inactive, expired');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status', 'expires_at']);
        });

        Schema::connection('ecommerce')->create('crm_coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamp('redeemed_at');

            $table->foreign('coupon_id')->references('id')->on('crm_coupons')->cascadeOnDelete();
            $table->index(['coupon_id', 'redeemed_at']);
            $table->index(['user_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_coupon_redemptions');
        Schema::connection('ecommerce')->dropIfExists('crm_coupons');
    }
};
