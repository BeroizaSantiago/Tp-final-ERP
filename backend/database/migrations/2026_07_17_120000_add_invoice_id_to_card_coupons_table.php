<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_coupons', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id')->constrained('invoices')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE card_coupons cc INNER JOIN invoices i ON i.full_number = cc.receipt_number SET cc.invoice_id = i.id WHERE cc.invoice_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('card_coupons', fn (Blueprint $table) => $table->dropConstrainedForeignId('invoice_id'));
    }
};
