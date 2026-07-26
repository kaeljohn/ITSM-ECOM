<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('slug', 150);
            $table->text('description')->nullable();
            $table->jsonb('criteria')->nullable()->comment('JSON rules for automatic segment membership');
            $table->boolean('is_auto')->default(false)->comment('Auto-calculated from criteria');
            $table->timestamps();

            $table->unique(['client_id', 'slug']);
        });

        Schema::connection('ecommerce')->create('crm_customer_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('segment_id');
            $table->timestamps();

            $table->unique(['customer_id', 'segment_id']);
            $table->foreign('customer_id')->references('id')->on('crm_customers')->cascadeOnDelete();
            $table->foreign('segment_id')->references('id')->on('crm_segments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_customer_segments');
        Schema::connection('ecommerce')->dropIfExists('crm_segments');
    }
};
