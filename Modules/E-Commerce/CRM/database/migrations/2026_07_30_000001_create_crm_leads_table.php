<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();

            // Contact info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();

            // Pipeline
            $table->string('status')->default('new')->index();
            // new → contacted → qualified → proposal → negotiation → won → lost

            $table->string('source')->nullable()->index();
            $table->decimal('expected_value', 12, 2)->default(0);
            $table->decimal('actual_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('probability')->default(10); // 0-100%

            // Dates
            $table->date('expected_close_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            // Assignment & notes
            $table->string('assigned_to')->nullable(); // sales rep name/email
            $table->unsignedBigInteger('customer_id')->nullable(); // linked CRM customer after conversion
            $table->text('notes')->nullable();

            // Activity log as JSON — lightweight timeline
            $table->json('activity_log')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'email']);

            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_leads');
    }
};
