<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('providers', function (Blueprint $table) {
        $table->string('vat_classification')->nullable()->after('identification_number');
        $table->string('gross_income_number')->nullable()->after('vat_classification');
        $table->string('province_name')->nullable()->after('city_name');
    });
}

    public function down(): void
{
    Schema::table('providers', function (Blueprint $table) {
        $table->dropColumn([
            'vat_classification',
            'gross_income_number',
            'province_name',
        ]);
    });
}
};
