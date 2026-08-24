<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->enum('value_type', ['fixed', 'percentage'])->default('fixed')->after('amount');
            $table->decimal('maximum_discount_amount', 15, 2)->nullable()->after('value_type');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['value_type', 'maximum_discount_amount']);
        });
    }
};
