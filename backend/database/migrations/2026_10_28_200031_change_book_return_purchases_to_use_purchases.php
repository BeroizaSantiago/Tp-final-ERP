<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_return_purchase_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_receipt_item_id']);
            $table->dropColumn('purchase_receipt_item_id');
        });

        Schema::table('book_return_purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_receipt_id']);
            $table->dropColumn('purchase_receipt_id');
        });

        Schema::table('book_return_purchases', function (Blueprint $table) {
            $table->foreignId('purchase_id')
                ->after('id')
                ->constrained('purchases')
                ->restrictOnDelete();
        });

        Schema::table('book_return_purchase_items', function (Blueprint $table) {
            $table->foreignId('purchase_item_id')
                ->after('book_return_purchase_id')
                ->constrained('purchase_items')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('book_return_purchase_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_item_id']);
            $table->dropColumn('purchase_item_id');
        });

        Schema::table('book_return_purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropColumn('purchase_id');
        });

        Schema::table('book_return_purchases', function (Blueprint $table) {
            $table->foreignId('purchase_receipt_id')
                ->constrained('purchase_receipts')
                ->restrictOnDelete();
        });

        Schema::table('book_return_purchase_items', function (Blueprint $table) {
            $table->foreignId('purchase_receipt_item_id')
                ->constrained('purchase_receipt_items')
                ->restrictOnDelete();
        });
    }
};
