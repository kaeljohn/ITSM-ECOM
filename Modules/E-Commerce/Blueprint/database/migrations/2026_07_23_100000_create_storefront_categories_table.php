<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    public function up(): void
    {
        Schema::connection('ecommerce')->create('storefront_categories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('storefront_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('storefront_categories');
    }
};