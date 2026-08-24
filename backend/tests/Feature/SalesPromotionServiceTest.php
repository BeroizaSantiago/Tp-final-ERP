<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Models\Products\SalesPromotion;
use App\Services\Sales\SalesPromotionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesPromotionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('products', function(Blueprint $t){$t->id();$t->string('name');$t->unsignedBigInteger('category_id')->nullable();$t->unsignedBigInteger('brand_id')->nullable();$t->timestamps();});
        Schema::create('sales_promotions', function(Blueprint $t){$t->id();$t->string('name');$t->string('promotion_type');$t->date('date_from')->nullable();$t->date('date_to')->nullable();$t->json('active_weekdays')->nullable();$t->string('currency_name')->nullable();$t->string('company_name')->nullable();$t->string('branch_name')->nullable();$t->string('price_list_name')->nullable();$t->json('payment_methods')->nullable();$t->string('applies_to');$t->string('discount_type');$t->decimal('discount_value',15,4);$t->string('display_mode');$t->decimal('minimum_amount',15,4);$t->decimal('minimum_quantity',15,4);$t->boolean('is_active');$t->timestamps();});
        Schema::create('sales_promotion_items', function(Blueprint $t){$t->id();$t->unsignedBigInteger('sales_promotion_id');$t->unsignedBigInteger('product_id')->nullable();$t->unsignedBigInteger('category_id')->nullable();$t->unsignedBigInteger('brand_id')->nullable();$t->timestamps();});
    }

    protected function tearDown(): void
    {
        foreach(['sales_promotion_items','sales_promotions','products'] as $table) Schema::dropIfExists($table);
        parent::tearDown();
    }

    public function test_only_compatible_promotions_are_offered_without_applying_them(): void
    {
        $product=Product::create(['name'=>'Remera']);
        $promotion=SalesPromotion::create(['name'=>'Lunes efectivo','promotion_type'=>'discount','date_from'=>'2026-07-01','active_weekdays'=>[1],'currency_name'=>'Pesos','payment_methods'=>['cash'],'applies_to'=>'product','discount_type'=>'percentage','discount_value'=>10,'display_mode'=>'line','minimum_amount'=>100,'minimum_quantity'=>1,'is_active'=>true]);
        $promotion->items()->create(['product_id'=>$product->id]);

        $context=['date'=>'2026-07-20','currency_name'=>'Pesos','payment_methods'=>['cash'],'items'=>[['product_id'=>$product->id,'quantity'=>2,'unit_price'=>100]]];
        $compatible=app(SalesPromotionService::class)->compatible($context);

        $this->assertCount(1,$compatible);
        $this->assertSame(20.0,(float)$compatible->first()->estimated_discount);
        $this->assertNull($product->fresh()->getAttribute('sales_promotion_id'));
        $this->assertCount(0,app(SalesPromotionService::class)->compatible(array_merge($context,['payment_methods'=>['transfer']])));
    }
}
