<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_items', 'product_id')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->after('invoice_id')
                    ->constrained('products')->nullOnDelete();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE invoice_items item INNER JOIN product_variants variant ON variant.id = item.product_variant_id SET item.product_id = variant.product_id WHERE item.product_id IS NULL');
        } else {
            DB::statement('UPDATE invoice_items SET product_id = (SELECT product_id FROM product_variants WHERE product_variants.id = invoice_items.product_variant_id) WHERE product_id IS NULL AND product_variant_id IS NOT NULL');
        }
        $this->backfillUnique('external_id', 'product_external_id');
        $this->backfillUnique('bar_code', 'product_barcode');
        $this->backfillUnique('code', 'product_code');
        $this->backfillUnique('reference_code', 'product_reference_code');
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }

    private function backfillUnique(string $productColumn, string $itemColumn): void
    {
        if (DB::getDriverName() !== 'mysql') {
            DB::statement("UPDATE invoice_items
                SET product_id = (
                    SELECT MIN(products.id)
                    FROM products
                    WHERE products.{$productColumn} = invoice_items.{$itemColumn}
                    GROUP BY products.{$productColumn}
                    HAVING COUNT(*) = 1
                )
                WHERE product_id IS NULL AND {$itemColumn} IS NOT NULL");

            return;
        }

        DB::statement("UPDATE invoice_items item
            INNER JOIN (
                SELECT {$productColumn} lookup_value, MIN(id) product_id
                FROM products
                WHERE {$productColumn} IS NOT NULL AND {$productColumn} <> ''
                GROUP BY {$productColumn}
                HAVING COUNT(*) = 1
            ) matched ON matched.lookup_value = item.{$itemColumn}
            SET item.product_id = matched.product_id
            WHERE item.product_id IS NULL");
    }
};
