<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    public function up(): void
    {
        Schema::connection('ecommerce')->create('storefront_listings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('storefront_category_id')->nullable();
            $table->string('name')->nullable(); // Optional override of inventory name
            $table->text('description')->nullable();
            $table->decimal('override_price', 10, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('storefront_category_id')->references('id')->on('storefront_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('storefront_listings');
    }
};