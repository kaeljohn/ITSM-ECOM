<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('ecommerce');

        if (! $schema->hasTable('storefront_listings')) {
            $schema->create('storefront_listings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('bom_id')->nullable()->index();
                $table->string('sku', 100)->nullable();
                $table->string('name', 160);
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0.00);
                $table->string('image_url')->nullable();
                $table->string('status', 30)->default('active');
                $table->timestamps();
            });
            return;
        }

        $schema->table('storefront_listings', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('storefront_listings', 'bom_id')) {
                $table->unsignedBigInteger('bom_id')->nullable()->index();
            }
            if (! $schema->hasColumn('storefront_listings', 'sku')) {
                $table->string('sku', 100)->nullable();
            }
            if (! $schema->hasColumn('storefront_listings', 'price')) {
                $table->decimal('price', 12, 2)->default(0.00);
            }
            if (! $schema->hasColumn('storefront_listings', 'status')) {
                $table->string('status', 30)->default('active');
            }
        });

        if ($schema->hasColumn('storefront_listings', 'override_price') && $schema->hasColumn('storefront_listings', 'price')) {
            DB::connection('ecommerce')->statement("UPDATE storefront_listings SET price = override_price WHERE price = 0 AND override_price IS NOT NULL");
        }

        if ($schema->hasColumn('storefront_listings', 'is_active') && $schema->hasColumn('storefront_listings', 'status')) {
            DB::connection('ecommerce')->statement("UPDATE storefront_listings SET status = CASE WHEN is_active = true THEN 'active' ELSE 'draft' END WHERE status IS NULL OR status = ''");
        }
    }

    public function down(): void
    {
        // No destruct on down
    }
};
