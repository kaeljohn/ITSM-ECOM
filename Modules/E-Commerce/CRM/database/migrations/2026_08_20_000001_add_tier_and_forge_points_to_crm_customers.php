<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->table('crm_customers', function (Blueprint $table) {
            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'tier')) {
                $table->string('tier', 20)->nullable()->after('average_order_value')
                    ->comment('bronze, silver, gold, platinum');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'forge_points')) {
                $table->integer('forge_points')->default(0)->after('tier');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'total_forge_points_earned')) {
                $table->integer('total_forge_points_earned')->default(0)->after('forge_points');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->table('crm_customers', function (Blueprint $table) {
            $table->dropColumn(['tier', 'forge_points', 'total_forge_points_earned']);
        });
    }
};
