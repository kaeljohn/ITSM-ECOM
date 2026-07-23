<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'ecommerce';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('ecommerce')->statement('DROP SERVER IF EXISTS hr_server CASCADE');
        DB::connection('ecommerce')->statement('DROP EXTENSION IF NOT EXISTS postgres_fdw CASCADE');
    }
};
