<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = DB::connection('ecommerce')->select("SELECT table_name FROM information_schema.columns WHERE column_name = 'client_id' AND table_schema = 'public'");
        
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            Schema::connection('ecommerce')->table($tableName, function (Blueprint $t) use ($tableName) {
                if (Schema::connection('ecommerce')->hasColumn($tableName, 'client_id')) {
                    $t->dropColumn('client_id');
                }
            });
            Schema::connection('ecommerce')->table($tableName, function (Blueprint $t) {
                $t->unsignedBigInteger('client_id')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = DB::connection('ecommerce')->select("SELECT table_name FROM information_schema.columns WHERE column_name = 'client_id' AND table_schema = 'public'");
        
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            DB::connection('ecommerce')->statement("ALTER TABLE \"$tableName\" ALTER COLUMN client_id TYPE uuid USING NULL::uuid");
        }
    }
};
