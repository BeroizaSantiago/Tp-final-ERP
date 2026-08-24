<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('credit_note_idempotency_key', 64)->nullable()->unique()->after('related_invoice_id');
            $table->timestamp('credit_note_stock_applied_at')->nullable()->after('stock_applied_at');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('original_invoice_item_id')->nullable()->after('invoice_id')
                ->constrained('invoice_items')->nullOnDelete();
            $table->index(['original_invoice_item_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['original_invoice_item_id', 'invoice_id']);
            $table->dropConstrainedForeignId('original_invoice_item_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['credit_note_idempotency_key']);
            $table->dropColumn(['credit_note_idempotency_key', 'credit_note_stock_applied_at']);
        });
    }
};
