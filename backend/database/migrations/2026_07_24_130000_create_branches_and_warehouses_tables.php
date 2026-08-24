<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
        });

        $now = now();
        $locations = collect();

        if (Schema::hasTable('inventory_items')) {
            $locations = DB::table('inventory_items')
                ->select(['branch_name', 'warehouse_name'])
                ->whereNotNull('branch_name')
                ->whereNotNull('warehouse_name')
                ->distinct()
                ->get();
        }

        if (Schema::hasTable('stock_adjustments')) {
            $locations = $locations->concat(
                DB::table('stock_adjustments')
                    ->select(['branch_name', 'warehouse_name'])
                    ->whereNotNull('branch_name')
                    ->whereNotNull('warehouse_name')
                    ->distinct()
                    ->get()
            );
        }

        $locations->prepend((object) [
            'branch_name' => 'SUCURSAL',
            'warehouse_name' => 'DEPÓSITO PRINCIPAL',
        ]);

        $locations
            ->filter(fn ($location) => trim((string) $location->branch_name) !== ''
                && trim((string) $location->warehouse_name) !== '')
            ->unique(fn ($location) => mb_strtolower(
                trim((string) $location->branch_name).'|'.trim((string) $location->warehouse_name)
            ))
            ->each(function ($location) use ($now) {
                $branchName = trim((string) $location->branch_name);
                $warehouseName = trim((string) $location->warehouse_name);

                DB::table('branches')->updateOrInsert(
                    ['name' => $branchName],
                    ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                );

                $branchId = DB::table('branches')->where('name', $branchName)->value('id');

                DB::table('warehouses')->updateOrInsert(
                    ['branch_id' => $branchId, 'name' => $warehouseName],
                    ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
    }
};
