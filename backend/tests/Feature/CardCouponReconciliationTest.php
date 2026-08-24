<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CardCouponReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => $this->base($t, fn () => [$t->string('name'), $t->string('email'), $t->string('password'), $t->rememberToken()]));
        Schema::create('banks', fn (Blueprint $t) => $this->base($t, fn () => [$t->string('name'), $t->boolean('is_active')->default(true)]));
        Schema::create('bank_accounts', fn (Blueprint $t) => $this->base($t, fn () => [$t->unsignedBigInteger('bank_id'), $t->string('bank_name')->nullable(), $t->string('account_number'), $t->boolean('is_active')->default(true)]));
        Schema::create('bank_movements', function (Blueprint $t) { $this->base($t, function () use ($t) { $t->unsignedBigInteger('bank_account_id'); $t->string('bank_account_name')->nullable(); $t->dateTime('issue_date'); $t->string('bank_concept_name')->nullable(); $t->decimal('amount', 15, 4); $t->boolean('reconciled'); $t->dateTime('council_date')->nullable(); $t->string('movement_type_name')->nullable(); $t->string('service_channel_name')->nullable(); $t->text('legend')->nullable(); $t->boolean('is_automatic'); $t->string('voucher')->nullable(); $t->string('mode')->nullable(); $t->dateTime('creation_date'); $t->string('created_by'); $t->boolean('is_active'); }); });
        Schema::create('card_coupons', function (Blueprint $t) { $this->base($t, function () use ($t) { $t->unsignedBigInteger('invoice_id')->nullable(); $t->string('coupon_status'); $t->decimal('coupon_amount', 15, 4); $t->decimal('commission', 15, 4)->default(0); }); });
        Schema::create('card_coupon_reconciliations', function (Blueprint $t) { $this->base($t, function () use ($t) { $t->string('number')->unique(); $t->string('settlement_number')->nullable(); $t->dateTime('issue_date'); $t->dateTime('accreditation_date'); $t->unsignedBigInteger('bank_id'); $t->unsignedBigInteger('bank_account_id'); $t->decimal('gross_amount',15,4); $t->decimal('commission_amount',15,4); $t->decimal('withholding_amount',15,4); $t->string('withholding_description')->nullable(); $t->decimal('other_discount_amount',15,4); $t->string('other_discount_description')->nullable(); $t->decimal('net_amount',15,4); $t->string('status'); $t->text('notes')->nullable(); $t->unsignedBigInteger('bank_movement_id')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); }); });
        Schema::create('card_coupon_reconciliation_items', function (Blueprint $t) { $this->base($t, function () use ($t) { $t->unsignedBigInteger('card_coupon_reconciliation_id'); $t->unsignedBigInteger('card_coupon_id')->unique(); $t->decimal('coupon_amount',15,4); $t->decimal('commission_amount',15,4); $t->decimal('net_amount',15,4); }); });
    }

    private function base(Blueprint $table, callable $columns): void
    {
        $table->id(); $columns(); $table->timestamps();
    }

    protected function tearDown(): void
    {
        foreach (['card_coupon_reconciliation_items','card_coupon_reconciliations','card_coupons','bank_movements','bank_accounts','banks','users'] as $table) Schema::dropIfExists($table);
        parent::tearDown();
    }

    public function test_it_creates_the_document_bank_movement_and_updates_coupons(): void
    {
        $user = User::create(['name' => 'Caja', 'email' => 'caja@example.com', 'password' => 'ERP123']);
        $bankId = Schema::getConnection()->table('banks')->insertGetId(['name'=>'Banco', 'is_active'=>1, 'created_at'=>now(), 'updated_at'=>now()]);
        $accountId = Schema::getConnection()->table('bank_accounts')->insertGetId(['bank_id'=>$bankId, 'bank_name'=>'Banco', 'account_number'=>'123', 'is_active'=>1, 'created_at'=>now(), 'updated_at'=>now()]);
        $couponIds = collect([[100,5],[50,5]])->map(fn ($values) => Schema::getConnection()->table('card_coupons')->insertGetId(['coupon_status'=>'Pendiente','coupon_amount'=>$values[0],'commission'=>$values[1],'created_at'=>now(),'updated_at'=>now()]))->all();

        $response = $this->actingAs($user)->postJson('/api/card-coupon-reconciliations', [
            'coupon_ids'=>$couponIds, 'settlement_number'=>'LIQ-1', 'issue_date'=>'2026-07-17', 'accreditation_date'=>'2026-07-17',
            'bank_id'=>$bankId, 'bank_account_id'=>$accountId, 'withholding_amount'=>5, 'other_discount_amount'=>2,
        ]);

        $response->assertCreated()->assertJsonPath('reconciliation.net_amount', '133.00');
        $this->assertDatabaseHas('card_coupon_reconciliations', ['gross_amount'=>150, 'commission_amount'=>10, 'net_amount'=>133]);
        $this->assertDatabaseHas('bank_movements', ['amount'=>133, 'is_automatic'=>1, 'mode'=>'card_coupon_reconciliation']);
        $this->assertSame(2, Schema::getConnection()->table('card_coupons')->where('coupon_status','Conciliado')->count());
    }
}
