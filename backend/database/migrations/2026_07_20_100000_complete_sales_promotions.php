<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_promotions', function (Blueprint $table) {
            $table->string('promotion_type')->default('discount')->after('name');
            $table->json('active_weekdays')->nullable()->after('date_to');
            $table->string('company_name')->nullable()->after('currency_name');
            $table->string('branch_name')->nullable()->after('company_name');
            $table->string('price_list_name')->nullable()->after('branch_name');
            $table->json('payment_methods')->nullable()->after('price_list_name');
            $table->string('display_mode')->default('line')->after('discount_value');
            $table->decimal('minimum_amount', 15, 4)->default(0)->after('display_mode');
            $table->decimal('minimum_quantity', 15, 4)->default(0)->after('minimum_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sales_promotion_id')->nullable()->after('client_id')
                ->constrained('sales_promotions')->nullOnDelete();
            $table->decimal('promotion_discount_amount', 15, 4)->default(0)->after('total_amount');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('manual_discount_percentage', 15, 4)->default(0)->after('discount_percentage');
            $table->decimal('promotion_discount_amount', 15, 4)->default(0)->after('manual_discount_percentage');
        });

        DB::table('invoice_items')->update(['manual_discount_percentage' => DB::raw('discount_percentage')]);
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['manual_discount_percentage', 'promotion_discount_amount']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_promotion_id');
            $table->dropColumn('promotion_discount_amount');
        });
        Schema::table('sales_promotions', function (Blueprint $table) {
            $table->dropColumn(['promotion_type','active_weekdays','company_name','branch_name','price_list_name','payment_methods','display_mode','minimum_amount','minimum_quantity']);
        });
    }
};
