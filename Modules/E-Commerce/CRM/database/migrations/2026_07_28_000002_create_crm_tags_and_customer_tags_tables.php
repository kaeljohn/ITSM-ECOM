<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('name', 100);
            $table->string('color', 20)->default('#6b7280')->comment('hex color for badge');
            $table->timestamps();

            $table->unique(['client_id', 'name']);
        });

        Schema::connection('ecommerce')->create('crm_customer_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['customer_id', 'tag_id']);
            $table->foreign('customer_id')->references('id')->on('crm_customers')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('crm_tags')->cascadeOnDelete();
            $table->index(['client_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_customer_tags');
        Schema::connection('ecommerce')->dropIfExists('crm_tags');
    }
};
