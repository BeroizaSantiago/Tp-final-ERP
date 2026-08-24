<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('arca_status')->nullable()->after('status_name');
            $table->string('arca_result')->nullable()->after('arca_status');
            $table->string('arca_cae')->nullable()->after('arca_result');
            $table->date('arca_cae_expiration')->nullable()->after('arca_cae');
            $table->integer('arca_voucher_number')->nullable()->after('arca_cae_expiration');
            $table->integer('arca_voucher_type')->nullable()->after('arca_voucher_number');
            $table->integer('arca_point_of_sale')->nullable()->after('arca_voucher_type');
            $table->json('arca_response')->nullable()->after('arca_point_of_sale');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'arca_status',
                'arca_result',
                'arca_cae',
                'arca_cae_expiration',
                'arca_voucher_number',
                'arca_voucher_type',
                'arca_point_of_sale',
                'arca_response',
            ]);
        });
    }
};
