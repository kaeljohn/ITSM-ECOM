<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->unique()->comment('FK to ecommerce.users');
            $table->string('email')->nullable()->index();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source', 50)->nullable()->comment('direct, social, referral, lead, organic');
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamp('last_purchase_at')->nullable();
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->jsonb('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'email']);
            $table->index(['client_id', 'last_purchase_at']);
            $table->index(['client_id', 'total_spent']);
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_customers');
    }
};
