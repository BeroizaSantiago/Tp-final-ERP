<?php

namespace App\Http\Controllers\Api\Products\Variants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * Controlador de Producto Variante.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Producto Variante del ERP.
 *
 * Las variantes se definen por la combinación de atributos maestros:
 * categoría, marca, editorial, modelo y colección.
 */
class ProductVariantController extends Controller
{
    private const MAX_IMAGE_KB = 2048;

    /** Campos que identifican una variante de forma única dentro de un producto. */
    private const ATTRIBUTE_FIELDS = ['category_id', 'brand_id', 'publisher_id', 'product_model_id', 'collection_id'];

    public function store(Request $request, StockService $stockService)
    {
        try {
            $productId = $request->integer('product_id');

            $data = $request->validate([
                'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
                'category_id' => ['nullable', 'exists:product_categories,id'],
                'brand_id' => ['nullable', 'exists:brands,id'],
                'publisher_id' => ['nullable', 'exists:publishers,id'],
                'product_model_id' => ['nullable', 'exists:product_models,id'],
                'collection_id' => ['nullable', 'exists:collections,id'],
                'sku' => ['nullable', 'string', 'max:255'],
                'bar_code' => ['nullable', 'string', 'max:255'],
                'price_a_with_tax' => ['nullable', 'numeric'],
                'current_stock' => ['nullable', 'numeric'],
                'image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],
                'image_url' => ['nullable', 'url:http,https', 'max:255'],
            ], [
                'image.image' => 'El archivo debe ser una imagen valida.',
                'image.max' => 'La imagen de la variante no puede superar 2 MB.',
                'product_id.exists' => 'El producto no existe o está inhabilitado.',
                'category_id.exists' => 'La categoría seleccionada no existe.',
                'brand_id.exists' => 'La marca seleccionada no existe.',
                'publisher_id.exists' => 'La editorial seleccionada no existe.',
                'product_model_id.exists' => 'El modelo seleccionado no existe.',
                'collection_id.exists' => 'La colección seleccionada no existe.',
            ]);

            // Buscar variante existente por combinación de atributos
            $existingVariant = ProductVariant::findByAttributes($productId, $data);

            // Validar unicidad de SKU y bar_code ignorando la variante existente
            $skuRule = $existingVariant
                ? Rule::unique('product_variants', 'sku')->ignore($existingVariant->id)
                : Rule::unique('product_variants', 'sku');
            $barCodeRule = $existingVariant
                ? Rule::unique('product_variants', 'bar_code')->ignore($existingVariant->id)
                : Rule::unique('product_variants', 'bar_code');

            $request->validate([
                'sku' => ['nullable', 'string', 'max:255', $skuRule],
                'bar_code' => ['nullable', 'string', 'max:255', $barCodeRule],
            ], [
                'sku.unique' => 'El SKU ya está utilizado por otra variante.',
                'bar_code.unique' => 'El código de barras ya está utilizado por otra variante.',
            ]);

            $wasUpdated = (bool) $existingVariant;
            $variant = DB::transaction(function () use ($request, $data, $existingVariant, $stockService) {
                if ($request->hasFile('image')) {
                    $data['image_url'] = $request->file('image')->store('product-variants', 'public');
                }

                unset($data['image']);
                $stockWasProvided = array_key_exists('current_stock', $data);
                $stock = (float) ($data['current_stock'] ?? 0);
                unset($data['current_stock'], $data['available_stock']);

                if ($existingVariant) {
                    $existingVariant->update($data);
                    $variant = $existingVariant->fresh();

                    if ($stockWasProvided) {
                        $stockService->setVariantStockAtDefaultLocation($variant->product, $variant, $stock);
                    }
                } else {
                    $variant = ProductVariant::create($data + [
                        'current_stock' => $stock,
                        'available_stock' => $stock,
                    ]);

                    $stockService->initializeVariantStock($variant->product, $variant, $stock);
                    $stockService->recalculateProductStock($variant->product);
                }

                return $variant->fresh()->load(['product', 'category', 'brand', 'publisher', 'model', 'collection']);
            });

            return response()->json([
                'message' => $wasUpdated
                    ? 'La variante existente fue actualizada.'
                    : 'La variante fue creada.',
                'updated_existing' => $wasUpdated,
                'variant' => $variant,
            ], $wasUpdated ? 200 : 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos de la variante no son validos.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function index(Request $request)
    {
        $query = ProductVariant::query()
            ->with(['product', 'category', 'brand', 'publisher', 'model', 'collection']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        return $query->paginate(min(100, max(10, $request->integer('per_page', 20))));
    }

    public function show(ProductVariant $productVariant)
    {
        return $productVariant->load(['product', 'category', 'brand', 'publisher', 'model', 'collection', 'inventoryItems']);
    }

    public function destroy(Request $request, ProductVariant $variant)
    {
        $product = $variant->product;

        DB::transaction(function () use ($variant) {
            $variant->inventoryItems()->delete();
            $variant->delete();
        });

        app(StockService::class)->recalculateProductStock($product);

        return response()->json([
            'message' => 'La variante fue eliminada.',
        ]);
    }
}
