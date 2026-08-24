<?php

namespace Tests\Feature;

use App\Services\Products\ProductBulkImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductBulkImportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->string('code')->nullable();
            $table->string('bar_code')->nullable();
            $table->string('reference_code')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('external_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('bar_code')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        parent::tearDown();
    }

    public function test_groups_kiboo_rows_as_one_product_with_three_variants(): void
    {
        $stockHeader = 'stock_depos_deposito_rios_lorena_beatriz_sur_leonas_sport';
        $rows = collect([
            $this->row(3832595, '0264NG000S', 'S', 'NEGRO', 2, $stockHeader),
            $this->row(3832596, '0264ROJ00M', 'M', 'ROJO', 3, $stockHeader),
            $this->row(3832597, '0264ROJ00S', 'S', 'ROJO', 4, $stockHeader),
        ]);

        $result = app(ProductBulkImportService::class)->simulate(
            $rows,
            array_keys($rows->first()),
            [$stockHeader => 'STOCK - DEPOS: DEPÓSITO RIOS LORENA BEATRIZ - SUR: LEONAS SPORT'],
            'gross'
        );

        $this->assertSame(1, $result['summary']['products']);
        $this->assertSame(3, $result['summary']['variants']);
        $this->assertSame(1, $result['summary']['new_products']);
        $this->assertSame(3, $result['summary']['new_variants']);
        $this->assertSame(0, $result['summary']['errors']);
        $this->assertTrue($result['groups'][0]['product']['has_variants']);
        $this->assertSame('000264', $result['groups'][0]['product']['bar_code']);
        $this->assertSame('DEPÓSITO RIOS LORENA BEATRIZ', $result['stock_locations'][0]['warehouse_name']);
        $this->assertSame('LEONAS SPORT', $result['stock_locations'][0]['branch_name']);
    }

    public function test_marks_the_whole_product_as_invalid_when_one_of_its_rows_has_an_error(): void
    {
        $stockHeader = 'stock_depos_deposito_rios_lorena_beatriz_sur_leonas_sport';
        $duplicate = $this->row(3832595, '0264NG000S', 'S', 'NEGRO', 2, $stockHeader);

        $result = app(ProductBulkImportService::class)->simulate(
            collect([$duplicate, $duplicate]),
            array_keys($duplicate),
            [$stockHeader => 'STOCK - DEPOS: DEPÓSITO RIOS LORENA BEATRIZ - SUR: LEONAS SPORT'],
            'gross'
        );

        $this->assertGreaterThan(0, $result['summary']['errors']);
        $this->assertSame(0, $result['summary']['valid_products']);
        $this->assertSame(1, $result['summary']['invalid_products']);
        $this->assertTrue($result['groups'][0]['has_errors']);
        $this->assertTrue($result['preview'][0]['has_errors']);
    }

    public function test_net_prices_without_aliquot_are_saved_as_net_and_do_not_assume_twenty_one_percent(): void
    {
        $stockHeader = 'stock_depos_deposito_rios_lorena_beatriz_sur_leonas_sport';
        $row = $this->row(3832595, '0264NG000S', 'S', 'NEGRO', 2, $stockHeader);
        $row['alicuota'] = '';
        $row['costo_del_producto'] = '1000';
        $row['precio_a'] = '2000';

        $result = app(ProductBulkImportService::class)->simulate(
            collect([$row]),
            array_keys($row),
            [$stockHeader => 'STOCK - DEPOS: DEPÓSITO RIOS LORENA BEATRIZ - SUR: LEONAS SPORT'],
            'net'
        );

        $product = $result['groups'][0]['product'];
        $this->assertSame(0.0, $product['tax']);
        $this->assertSame(1000.0, $product['cost_net']);
        $this->assertSame(1000.0, $product['cost_with_tax']);
        $this->assertSame(2000.0, $product['price_a']);
        $this->assertSame(2000.0, $product['price_a_with_tax']);
        $this->assertStringContainsString('IVA 0%', implode(' ', $result['warnings']));
    }

    private function row(int $variantId, string $barcode, string $size, string $color, int $stock, string $stockHeader): array
    {
        return [
            'id_kiboo' => '1531378',
            'productvariantid_kiboo' => (string) $variantId,
            'nombre' => 'CANILLERAS DE FUTBOL KICK PROYEC',
            'codigo_de_barra' => $barcode,
            'codigo_de_barra_principal' => '000264',
            'codigo_de_referencia' => '413',
            'unidad_de_medida' => 'Unidades',
            'moneda' => 'Pesos',
            'alicuota' => '21',
            'tipo_de_talle' => 'IMPORTACION',
            'talle' => $size,
            'color' => $color,
            'servicio' => 'N',
            $stockHeader => (string) $stock,
        ];
    }
}
