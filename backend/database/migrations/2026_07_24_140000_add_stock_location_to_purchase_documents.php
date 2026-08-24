<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('branch_name')->nullable()->after('provider_name');
            $table->string('warehouse_name')->nullable()->after('branch_name');
        });

    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['branch_name', 'warehouse_name']);
        });
    }
};
