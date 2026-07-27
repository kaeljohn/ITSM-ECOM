<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->table('crm_customers', function (Blueprint $table) {
            // ── Customer Health & Engagement ──
            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'engagement_score')) {
                $table->decimal('engagement_score', 5, 2)->default(0)->after('notes')
                    ->comment('0.00 – 5.00 weighted engagement score');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'churn_risk')) {
                $table->string('churn_risk', 20)->default('low')->after('engagement_score')
                    ->comment('low, medium, high');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'opt_in_email')) {
                $table->boolean('opt_in_email')->default(false)->after('churn_risk');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'opt_in_sms')) {
                $table->boolean('opt_in_sms')->default(false)->after('opt_in_email');
            }

            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'opted_in_at')) {
                $table->timestamp('opted_in_at')->nullable()->after('opt_in_sms');
            }

            // ── Last Engagement ──
            if (!Schema::connection('ecommerce')->hasColumn('crm_customers', 'last_engaged_at')) {
                $table->timestamp('last_engaged_at')->nullable()->after('opted_in_at')
                    ->comment('Last interaction of any kind (order, ticket, email, etc.)');
            }

            // ── Performance Indexes (guarded for safe re-runs) ──
            if (!Schema::connection('ecommerce')->hasIndex('crm_customers', 'crm_cust_engagement_idx')) {
                $table->index(['client_id', 'engagement_score'], 'crm_cust_engagement_idx');
            }
            if (!Schema::connection('ecommerce')->hasIndex('crm_customers', 'crm_cust_churn_idx')) {
                $table->index(['client_id', 'churn_risk'], 'crm_cust_churn_idx');
            }
            if (!Schema::connection('ecommerce')->hasIndex('crm_customers', 'crm_cust_optin_idx')) {
                $table->index(['client_id', 'opt_in_email', 'opt_in_sms'], 'crm_cust_optin_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->table('crm_customers', function (Blueprint $table) {
            $table->dropColumn([
                'engagement_score',
                'churn_risk',
                'opt_in_email',
                'opt_in_sms',
                'opted_in_at',
                'last_engaged_at',
            ]);

            $table->dropIndex('crm_cust_engagement_idx');
            $table->dropIndex('crm_cust_churn_idx');
            $table->dropIndex('crm_cust_optin_idx');
        });
    }
};
