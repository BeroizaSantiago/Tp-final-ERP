<?php

namespace App\Http\Controllers\Api\Products\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona el catalogo de productos disponible mediante la API.
 *
 * Permite buscar productos por sus principales codigos o por nombre,
 * consultar su detalle y registrar productos con precios y existencias.
 */
class ProductController extends Controller
{
    private const MAX_IMAGE_KB = 2048;
    private const MAX_IMAGES = 6;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $isGenericSaleCode = in_array(mb_strtoupper($search), ['ZZ', '00', '0000'], true);

        if ($request->boolean('lookup') && mb_strlen($search) < 3 && ! $isGenericSaleCode) {
            return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0]);
        }

        return Product::with([
            'category',
            'brand',
            'model',
            'size',
            'color',
            'variants.size',
            'variants.color',
            'images',
        ])
            ->when($request->boolean('lookup'), fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search, $isGenericSaleCode) {
                $query->where(function ($query) use ($search, $isGenericSaleCode) {
                    if ($isGenericSaleCode) {
                        $query->whereRaw('UPPER(TRIM(code)) = ?', [mb_strtoupper($search)])
                            ->orWhereRaw('UPPER(TRIM(reference_code)) = ?', [mb_strtoupper($search)])
                            ->orWhereRaw('UPPER(TRIM(bar_code)) = ?', [mb_strtoupper($search)]);
                        return;
                    }

                    $query->where('name', 'like', "%{$search}%")
                    ->orWhere('bar_code', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', "%{$search}%")->orWhere('bar_code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function show(Product $product)
    {
        return $product->load([
            'category',
            'brand',
            'model',
            'size',
            'color',
            'inventoryItems',
            'variants.size',
            'variants.color',
            'images',
        ]);
    }

    public function scanPreview(Request $request)
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);
        $barcode = trim($data['barcode']);

        $variant = ProductVariant::query()
            ->with(['size:id,name', 'color:id,name'])
            ->where('bar_code', $barcode)
            ->where('is_active', true)
            ->first();

        $product = $variant
            ? Product::query()->find($variant->product_id)
            : Product::query()
                ->where(function ($query) use ($barcode) {
                    $query->where('bar_code', $barcode)
                        ->orWhere('code', $barcode)
                        ->orWhere('reference_code', $barcode);
                })
                ->first();

        if (! $product || ! $product->is_active) {
            return response()->json([
                'message' => 'No existe un producto activo con el código escaneado.',
            ], 404);
        }

        $product->load([
            'category:id,name',
            'brand:id,name',
            'model:id,name',
            'images',
            'variants' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['size:id,name', 'color:id,name']),
        ]);
        $image = $variant?->image_full_url
            ?: $product->images->first()?->full_url
            ?: $product->image_full_url;
        $sizes = $product->variants->pluck('size.name')->filter()->unique()->values();
        $colors = $product->variants->pluck('color.name')->filter()->unique()->values();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'barcode' => $variant?->bar_code ?: $product->bar_code,
            'image_url' => $image,
            'category' => $product->getRelation('category')?->name ?: $product->getRawOriginal('category'),
            'brand' => $product->getRelation('brand')?->name ?: $product->getRawOriginal('brand'),
            'model' => $product->getRelation('model')?->name ?: $product->getRawOriginal('model'),
            'size' => $variant?->size?->name,
            'color' => $variant?->color?->name,
            'sizes' => $sizes,
            'colors' => $colors,
            'price' => (float) ($variant?->price_a_with_tax ?? $product->price_a_with_tax ?? 0),
            'currency' => $product->currency_symbol ?: '$',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'external_id' => ['nullable'],
                'code' => ['nullable', 'string', 'max:255'],
                'bar_code' => ['nullable', 'string', 'max:255'],
                'reference_code' => ['nullable', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'currency_symbol' => ['nullable', 'string'],
                'currency_name' => ['nullable', 'string'],
                'product_type_name' => ['nullable', 'string'],
                'aliquot_name' => ['nullable', 'string'],
                'brand' => ['nullable', 'string'],
                'model' => ['nullable', 'string'],
                'category' => ['nullable', 'string'],
                'unit_measure_name' => ['nullable', 'string'],
                'has_variants' => ['nullable', 'boolean'],
                'auto_calculate_tax' => ['nullable', 'boolean'],
                'cost_with_discount' => ['nullable', 'numeric', 'min:0'],
                'cost_with_discount_with_tax_aliquot' => ['nullable', 'numeric', 'min:0'],
                'price_a' => ['nullable', 'numeric', 'min:0'],
                'price_a_with_tax' => ['nullable', 'numeric', 'min:0'],
                'markup_a' => ['nullable', 'numeric', 'min:0'],

                'price_b' => ['nullable', 'numeric', 'min:0'],
                'price_b_with_tax' => ['nullable', 'numeric', 'min:0'],
                'markup_b' => ['nullable', 'numeric', 'min:0'],

                'price_c' => ['nullable', 'numeric', 'min:0'],
                'price_c_with_tax' => ['nullable', 'numeric', 'min:0'],
                'markup_c' => ['nullable', 'numeric', 'min:0'],

                'price_d' => ['nullable', 'numeric', 'min:0'],
                'price_d_with_tax' => ['nullable', 'numeric', 'min:0'],
                'markup_d' => ['nullable', 'numeric', 'min:0'],
                'current_stock' => ['nullable', 'numeric'],
                'available_stock' => ['nullable', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'category_id' => ['nullable', 'exists:product_categories,id'],
                'brand_id' => ['nullable', 'exists:brands,id'],
                'product_model_id' => ['nullable', 'exists:product_models,id'],
                'size_id' => ['nullable', 'exists:sizes,id'],
                'color_id' => ['nullable', 'exists:colors,id'],
                'weight' => ['nullable', 'numeric'],
                'height' => ['nullable', 'numeric'],
                'width' => ['nullable', 'numeric'],
                'length' => ['nullable', 'numeric'],
                'images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
                'images.*' => ['image', 'max:' . self::MAX_IMAGE_KB],
                'image_urls' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
                'image_urls.*' => ['url:http,https', 'max:255', 'distinct'],
            ], [
                'images.max' => 'Podés cargar como máximo 6 imágenes.',
                'images.*.image' => 'Todos los archivos deben ser imágenes válidas.',
                'images.*.max' => 'Cada imagen puede pesar como máximo 2 MB.',
            ]);

            $tax = match ($data['aliquot_name'] ?? 'IVA 21%') {
                'IVA 10,5%' => 10.5,
                'IVA 27%' => 27,
                'Exento' => 0,
                default => 21,
            };

            if ($request->boolean('auto_calculate_tax')) {
                $this->calculateTaxInclusivePrices($data, $tax);
            }

            $imageUrls = $data['image_urls'] ?? [];
            unset($data['images'], $data['image_urls'], $data['auto_calculate_tax']);

            $data['has_variants'] = $request->boolean('has_variants', true);
            // En el alta no hay un selector de estado: el producto debe nacer
            // habilitado. Definirlo también en el modelo evita que el valor
            // por defecto de MySQL todavía aparezca como null en memoria al
            // inicializar el stock de un producto sin variantes.
            $data['is_active'] = $request->has('is_active')
                ? $request->boolean('is_active')
                : true;

            if (! $data['has_variants'] && empty($data['bar_code']) && empty($data['code']) && empty($data['reference_code'])) {
                throw ValidationException::withMessages([
                    'bar_code' => 'Un producto sin variantes debe tener código, código de referencia o código de barras.',
                ]);
            }

            if (count($request->file('images', [])) + count($imageUrls) > self::MAX_IMAGES) {
                throw ValidationException::withMessages(['images' => 'Podés cargar como máximo 6 imágenes en total.']);
            }

            $product = DB::transaction(function () use ($data, $request, $imageUrls) {
                $product = Product::create($data);

                $position = 0;
                foreach ($imageUrls as $url) {
                    $product->images()->create(['path' => $url, 'position' => $position++]);
                }
                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create(['path' => $path, 'position' => $position++]);
                }

                if ($primaryImage = $product->images()->value('path')) {
                    $product->update(['image_url' => $primaryImage]);
                }

                if (! $product->has_variants) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $product->code ?: $product->reference_code,
                        'bar_code' => $product->bar_code,
                        'price_a_with_tax' => $product->price_a_with_tax,
                        'current_stock' => $product->current_stock ?? 0,
                        'available_stock' => $product->current_stock ?? 0,
                        'is_active' => true,
                    ]);

                    $stockService = app(StockService::class);
                    $stockService->initializeVariantStock(
                        $product,
                        $variant,
                        (float) ($product->current_stock ?? 0)
                    );
                    $stockService->recalculateProductStock($product);
                }

                return $product;
            });

            return response()->json(
                $product->load([
                    'category',
                    'brand',
                    'model',
                    'size',
                    'color',
                    'images',
                ]),
                201
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos del producto no son validos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se pudo guardar el producto en la base de datos.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ocurrio un error inesperado al guardar el producto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable'],
            'bar_code' => ['nullable', 'string'],
            'reference_code' => ['nullable', 'string'],
            'name' => ['required', 'string'],

            'currency_symbol' => ['nullable', 'string'],
            'currency_name' => ['nullable', 'string'],
            'product_type_name' => ['nullable', 'string'],
            'aliquot_name' => ['nullable', 'string'],

            'brand' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
                'unit_measure_name' => ['nullable', 'string'],
                'has_variants' => ['nullable', 'boolean'],
                'auto_calculate_tax' => ['nullable', 'boolean'],
                'cost_with_discount' => ['nullable', 'numeric', 'min:0'],
                'cost_with_discount_with_tax_aliquot' => ['nullable', 'numeric', 'min:0'],

            'price_a' => ['nullable', 'numeric', 'min:0'],
            'price_a_with_tax' => ['nullable', 'numeric', 'min:0'],
            'markup_a' => ['nullable', 'numeric', 'min:0'],

            'price_b' => ['nullable', 'numeric', 'min:0'],
            'price_b_with_tax' => ['nullable', 'numeric', 'min:0'],
            'markup_b' => ['nullable', 'numeric', 'min:0'],

            'price_c' => ['nullable', 'numeric', 'min:0'],
            'price_c_with_tax' => ['nullable', 'numeric', 'min:0'],
            'markup_c' => ['nullable', 'numeric', 'min:0'],

            'price_d' => ['nullable', 'numeric', 'min:0'],
            'price_d_with_tax' => ['nullable', 'numeric', 'min:0'],
            'markup_d' => ['nullable', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'available_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            'category_id' => ['nullable', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'product_model_id' => ['nullable', 'exists:product_models,id'],
            'size_id' => ['nullable', 'exists:sizes,id'],
            'color_id' => ['nullable', 'exists:colors,id'],

            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],

            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:' . self::MAX_IMAGE_KB],
            'image_urls' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'image_urls.*' => ['url:http,https', 'max:255', 'distinct'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
        ]);

        $removeIds = collect($data['remove_image_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $imagesToRemove = $product->images()->whereIn('id', $removeIds)->get();
        $remainingCount = $product->images()->whereNotIn('id', $removeIds)->count();
        $newImages = $request->file('images', []);
        $newImageUrls = $data['image_urls'] ?? [];

        if ($remainingCount + count($newImages) + count($newImageUrls) > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'El producto puede tener como máximo 6 imágenes.',
            ]);
        }

        if ($request->boolean('auto_calculate_tax')) {
            $tax = match ($data['aliquot_name'] ?? 'IVA 21%') {
                'IVA 10,5%' => 10.5,
                'IVA 27%' => 27,
                'Exento' => 0,
                default => 21,
            };
            $this->calculateTaxInclusivePrices($data, $tax);
        }

        unset($data['images'], $data['image_urls'], $data['remove_image_ids'], $data['auto_calculate_tax']);

        DB::transaction(function () use ($product, $data, $imagesToRemove, $newImages, $newImageUrls) {
            $product->update($data);

            // Las variantes no administran un precio independiente en la
            // interfaz actual: heredan el Precio Final A del producto padre.
            // Mantener la columna sincronizada evita mostrar o facturar el
            // valor anterior después de editar el producto.
            if (array_key_exists('price_a_with_tax', $data)) {
                $product->variants()->update([
                    'price_a_with_tax' => $data['price_a_with_tax'],
                ]);
            }

            foreach ($imagesToRemove as $image) {
                if (! filter_var($image->path, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }

            $position = (int) $product->images()->max('position') + 1;
            foreach ($newImageUrls as $url) {
                $product->images()->create(['path' => $url, 'position' => $position++]);
            }
            foreach ($newImages as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create(['path' => $path, 'position' => $position++]);
            }

            $product->update([
                'image_url' => $product->images()->value('path'),
            ]);
        });

        return response()->json($product->fresh()->load('images'), 200);
    }

    public function updateStatus(Request $request, Product $product)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $product->update(['is_active' => $data['is_active']]);

        return response()->json([
            'message' => $product->is_active
                ? 'Producto habilitado correctamente.'
                : 'Producto dado de baja correctamente.',
            'product' => $product->fresh(),
        ]);
    }

    private function calculateTaxInclusivePrices(array &$data, float $tax): void
    {
        $fields = [
            'cost_with_discount' => 'cost_with_discount_with_tax_aliquot',
            'price_a' => 'price_a_with_tax',
            'price_b' => 'price_b_with_tax',
            'price_c' => 'price_c_with_tax',
            'price_d' => 'price_d_with_tax',
        ];

        foreach ($fields as $netField => $taxField) {
            if (array_key_exists($netField, $data) && $data[$netField] !== null) {
                $data[$taxField] = round((float) $data[$netField] * (1 + $tax / 100), 2);
            }
        }
    }
}
