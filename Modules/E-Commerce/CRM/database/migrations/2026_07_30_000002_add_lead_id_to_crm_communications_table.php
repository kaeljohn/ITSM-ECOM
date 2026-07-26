<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->table('crm_communications', function (Blueprint $table) {
            if (!Schema::connection('ecommerce')->hasColumn('crm_communications', 'lead_id')) {
                $table->unsignedBigInteger('lead_id')->nullable()->index()->after('customer_id');

                $table->foreign('lead_id')
                    ->references('id')
                    ->on('crm_leads')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->table('crm_communications', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
