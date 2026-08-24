<?php

namespace Tests\Feature;

use App\Models\Finance\CashBox;
use App\Models\Finance\CashSheet;
use App\Models\User;
use App\Services\Finance\TreasuryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TreasuryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email'); $t->string('password'); $t->rememberToken(); $t->timestamps(); });
        Schema::create('cash_boxes', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('box_type_name')->nullable(); $t->string('branch_name')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps(); });
        Schema::create('cash_sheets', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('cash_box_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->integer('number')->nullable(); $t->string('cash_box_name')->nullable(); $t->string('status_name'); $t->dateTime('opening_date')->nullable(); $t->dateTime('closing_date')->nullable(); $t->timestamps(); });
        Schema::create('cash_sheet_movements', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('cash_sheet_id'); $t->unsignedBigInteger('cash_box_id')->nullable(); $t->unsignedBigInteger('origin_cash_box_id')->nullable(); $t->unsignedBigInteger('origin_cash_sheet_id')->nullable()->unique(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('movement_type'); $t->string('payment_method'); $t->boolean('affects_cash_balance'); $t->decimal('amount',15,2); $t->string('document_number')->nullable(); $t->string('reference')->nullable(); $t->text('description')->nullable(); $t->string('status'); $t->timestamps(); });
    }

    protected function tearDown(): void
    {
        foreach (['cash_sheet_movements','cash_sheets','cash_boxes','users'] as $table) Schema::dropIfExists($table);
        parent::tearDown();
    }

    public function test_treasury_controls_opening_and_receives_cash_closures(): void
    {
        $service = app(TreasuryService::class);
        $treasury = CashBox::create(['name'=>'Tesorería','box_type_name'=>'TESORERIA','branch_name'=>'Central','branch_id'=>null]);
        $salesBox = CashBox::create(['name'=>'Caja 1','box_type_name'=>'CAJA','branch_name'=>'Central','branch_id'=>884]);

        try {
            $service->ensureTreasuryAllowsOpening($salesBox);
            $this->fail('La apertura debió ser rechazada.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        CashSheet::create(['cash_box_id'=>$treasury->id,'cash_box_name'=>'Tesorería','status_name'=>'Abierta','opening_date'=>now()]);
        $service->ensureTreasuryAllowsOpening($salesBox);

        $closedSheet = CashSheet::create(['cash_box_id'=>$salesBox->id,'cash_box_name'=>'Caja 1','number'=>15,'status_name'=>'Cerrada','opening_date'=>now(),'closing_date'=>now()]);
        $user = User::create(['name'=>'Cajero','email'=>'cajero@example.com','password'=>'ERP123']);
        $this->actingAs($user);
        $movement = $service->receiveCashClosure($closedSheet->load('cashBox'), 100, 10);

        $this->assertSame('90.00', $movement->amount);
        $this->assertSame($closedSheet->id, $movement->origin_cash_sheet_id);
        $this->assertSame($salesBox->id, $movement->origin_cash_box_id);
    }
}
