<?php

namespace App\Http\Controllers\Api\Products\Variants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Products\ProductVariant;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * Controlador de Producto Variante.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Producto Variante del ERP.
 */
class ProductVariantController extends Controller
{
    private const MAX_IMAGE_KB = 2048;

    public function store(Request $request, StockService $stockService)
    {
        try {
            $productId = $request->integer('product_id');
            $sizeId = $request->filled('size_id') ? $request->integer('size_id') : null;
            $colorId = $request->filled('color_id') ? $request->integer('color_id') : null;
            $existingVariant = ProductVariant::query()
                ->where('product_id', $productId)
                ->when($sizeId, fn ($query) => $query->where('size_id', $sizeId), fn ($query) => $query->whereNull('size_id'))
                ->when($colorId, fn ($query) => $query->where('color_id', $colorId), fn ($query) => $query->whereNull('color_id'))
                ->first();

            $data = $request->validate([
                'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
                'size_id' => ['nullable', 'exists:sizes,id'],
                'color_id' => ['nullable', 'exists:colors,id'],
                'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($existingVariant)],
                'bar_code' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'bar_code')->ignore($existingVariant)],
                'price_a_with_tax' => ['nullable', 'numeric'],
                'current_stock' => ['nullable', 'numeric'],
                'image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],
                'image_url' => ['nullable', 'url:http,https', 'max:255'],
            ], [
                'image.image' => 'El archivo debe ser una imagen valida.',
                'image.max' => 'La imagen de la variante no puede superar 2 MB.',
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

                return $variant->fresh()->load('product', 'size', 'color');
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
}
