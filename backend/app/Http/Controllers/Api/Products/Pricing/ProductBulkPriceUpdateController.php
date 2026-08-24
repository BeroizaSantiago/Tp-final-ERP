<?php

namespace App\Http\Controllers\Api\Products\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Products\Brand;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controlador de Producto Bulk Precio Update.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Producto Bulk Precio Update del ERP.
 */
class ProductBulkPriceUpdateController extends Controller
{
    public function simulate(Request $request)
    {
        $data = $request->validate([
            'price_list' => [
                'required',
                'in:all,a,b,c,d',
            ],

            'price_type' => [
                'required',
                'in:net,with_tax',
            ],

            'percentage' => [
                'required',
                'numeric',
                'min:0',
            ],

            'operation' => [
                'required',
                'in:add,subtract',
            ],

            'round_decimals' => [
                'nullable',
                'integer',
                'min:0',
                'max:4',
            ],

            'round_direction' => [
                'nullable',
                'in:normal,up,down',
            ],

            'category_id' => [
                'nullable',
                'exists:product_categories,id',
            ],

            'brand_id' => [
                'nullable',
                'exists:brands,id',
            ],

            'product_model_id' => [
                'nullable',
                'exists:product_models,id',
            ],

            'currency_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'product_id' => [
                'nullable',
                'exists:products,id',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $products = $this->buildProductQuery($data)
            ->orderBy('name')
            ->get();

        $lists = $data['price_list'] === 'all'
            ? ['a', 'b', 'c', 'd']
            : [$data['price_list']];

        $percentage = (float) $data['percentage'];
        $decimals = (int) ($data['round_decimals'] ?? 2);
        $direction = $data['round_direction'] ?? 'normal';

        $preview = [];

        foreach ($products as $product) {
            $changes = [];
            $categoryRelation = $product->getRelation('category');
            $brandRelation = $product->getRelation('brand');
            $modelRelation = $product->getRelation('model');

            foreach ($lists as $list) {
                $priceField = $data['price_type'] === 'net'
                    ? "price_{$list}"
                    : "price_{$list}_with_tax";

                $oldValue = (float) ($product->{$priceField} ?? 0);

                $newValue = $this->calculateNewValue(
                    $oldValue,
                    $percentage,
                    $data['operation'],
                    $decimals,
                    $direction
                );

                $changes[] = [
                    'list' => strtoupper($list),
                    'field' => $priceField,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'difference' => $newValue - $oldValue,
                    'difference_percentage' => $oldValue > 0
                        ? (($newValue - $oldValue) / $oldValue) * 100
                        : null,
                ];
            }

            $preview[] = [
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'category' => $categoryRelation?->name ?: $product->getAttribute('category'),
                'brand' => $brandRelation?->name ?: $product->getAttribute('brand'),
                'model' => $modelRelation?->name ?: $product->getAttribute('model'),
                'currency_name' => $product->currency_name,
                'aliquot_name' => $product->aliquot_name,
                'changes' => $changes,
            ];
        }

        $token = Str::uuid()->toString();

        Cache::put(
            "product_bulk_price_update_{$token}",
            [
                'product_ids' => $products->pluck('id')->all(),
                'price_list' => $data['price_list'],
                'price_type' => $data['price_type'],
                'percentage' => $percentage,
                'operation' => $data['operation'],
                'round_decimals' => $decimals,
                'round_direction' => $direction,
                'preview' => $preview,
            ],
            now()->addMinutes(30)
        );

        $changes = collect($preview)
            ->flatMap(fn (array $item) => $item['changes']);

        return response()->json([
            'token' => $token,
            'preview' => $preview,

            'summary' => [
                'products' => count($preview),
                'price_changes' => $changes->count(),

                'increases' => $changes
                    ->filter(fn (array $change) => $change['difference'] > 0)
                    ->count(),

                'decreases' => $changes
                    ->filter(fn (array $change) => $change['difference'] < 0)
                    ->count(),

                'unchanged' => $changes
                    ->filter(fn (array $change) => $change['difference'] == 0)
                    ->count(),

                'average_variation' => $changes
                    ->whereNotNull('difference_percentage')
                    ->avg('difference_percentage'),
            ],
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'token' => [
                'required',
                'string',
            ],
        ]);

        $cacheKey = "product_bulk_price_update_{$data['token']}";
        $simulation = Cache::get($cacheKey);

        if (!$simulation) {
            return response()->json([
                'message' => 'La simulación no existe o venció. Volvé a simular los precios.',
            ], 422);
        }

        $updatedProducts = 0;
        $updatedPrices = 0;

        DB::transaction(function () use (
            $simulation,
            &$updatedProducts,
            &$updatedPrices
        ) {
            $products = Product::whereIn(
                'id',
                $simulation['product_ids']
            )->where('is_active', true)->get();

            $lists = $simulation['price_list'] === 'all'
                ? ['a', 'b', 'c', 'd']
                : [$simulation['price_list']];

            foreach ($products as $product) {
                $productChanged = false;

                foreach ($lists as $list) {
                    $priceField = $simulation['price_type'] === 'net'
                        ? "price_{$list}"
                        : "price_{$list}_with_tax";

                    $oldValue = (float) ($product->{$priceField} ?? 0);

                    $newValue = $this->calculateNewValue(
                        $oldValue,
                        (float) $simulation['percentage'],
                        $simulation['operation'],
                        (int) $simulation['round_decimals'],
                        $simulation['round_direction']
                    );

                    $product->{$priceField} = $newValue;

                    $this->recalculateRelatedPrice(
                        $product,
                        $list,
                        $simulation['price_type'],
                        (int) $simulation['round_decimals'],
                        $simulation['round_direction']
                    );

                    $productChanged = true;
                    $updatedPrices++;
                }

                if ($productChanged) {
                    $product->save();
                    $updatedProducts++;
                }
            }
        });

        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'La actualización masiva se realizó correctamente.',
            'updated_products' => $updatedProducts,
            'updated_prices' => $updatedPrices,
        ]);
    }

    private function buildProductQuery(array $data): Builder
    {
        $categoryName = ! empty($data['category_id'])
            ? ProductCategory::whereKey($data['category_id'])->value('name')
            : null;
        $brandName = ! empty($data['brand_id'])
            ? Brand::whereKey($data['brand_id'])->value('name')
            : null;
        $modelName = ! empty($data['product_model_id'])
            ? ProductModel::whereKey($data['product_model_id'])->value('name')
            : null;

        return Product::query()
            ->where('is_active', true)
            ->with([
                'category',
                'brand',
                'model',
            ])
            ->when(
                !empty($data['category_id']),
                fn (Builder $query) => $query->where(function (Builder $filter) use ($data, $categoryName) {
                    $filter->where('category_id', $data['category_id']);
                    if ($categoryName) {
                        $filter->orWhere('category', $categoryName);
                    }
                })
            )
            ->when(
                !empty($data['brand_id']),
                fn (Builder $query) => $query->where(function (Builder $filter) use ($data, $brandName) {
                    $filter->where('brand_id', $data['brand_id']);
                    if ($brandName) {
                        $filter->orWhere('brand', $brandName);
                    }
                })
            )
            ->when(
                !empty($data['product_model_id']),
                fn (Builder $query) => $query->where(function (Builder $filter) use ($data, $modelName) {
                    $filter->where('product_model_id', $data['product_model_id']);
                    if ($modelName) {
                        $filter->orWhere('model', $modelName);
                    }
                })
            )
            ->when(
                !empty($data['currency_name']),
                fn (Builder $query) => $query->where(
                    'currency_name',
                    $data['currency_name']
                )
            )
            ->when(
                !empty($data['product_id']),
                fn (Builder $query) => $query->where(
                    'id',
                    $data['product_id']
                )
            )
            ->when(
                !empty($data['search']),
                function (Builder $query) use ($data) {
                    $search = $data['search'];

                    $query->where(function (Builder $subquery) use ($search) {
                        $subquery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('bar_code', 'like', "%{$search}%")
                            ->orWhere(
                                'reference_code',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            );
    }

    private function calculateNewValue(
        float $oldValue,
        float $percentage,
        string $operation,
        int $decimals,
        string $direction
    ): float {
        $variation = $oldValue * ($percentage / 100);

        $newValue = $operation === 'subtract'
            ? $oldValue - $variation
            : $oldValue + $variation;

        return $this->roundValue(
            max(0, $newValue),
            $decimals,
            $direction
        );
    }

    private function recalculateRelatedPrice(
        Product $product,
        string $list,
        string $priceType,
        int $decimals,
        string $direction
    ): void {
        $tax = $this->getTaxPercentage(
            $product->aliquot_name
        );

        $netField = "price_{$list}";
        $taxField = "price_{$list}_with_tax";

        if ($priceType === 'net') {
            $withTax = (float) $product->{$netField}
                * (1 + $tax / 100);

            $product->{$taxField} = $this->roundValue(
                $withTax,
                $decimals,
                $direction
            );

            return;
        }

        $netValue = $tax > 0
            ? (float) $product->{$taxField}
                / (1 + $tax / 100)
            : (float) $product->{$taxField};

        $product->{$netField} = $this->roundValue(
            $netValue,
            $decimals,
            $direction
        );
    }

    private function roundValue(
        float $value,
        int $decimals,
        string $direction
    ): float {
        $factor = 10 ** $decimals;

        return match ($direction) {
            'up' => ceil($value * $factor) / $factor,
            'down' => floor($value * $factor) / $factor,
            default => round($value, $decimals),
        };
    }

    private function getTaxPercentage(?string $aliquotName): float
    {
        $text = strtolower((string) $aliquotName);

        if (
            str_contains($text, '10,5') ||
            str_contains($text, '10.5')
        ) {
            return 10.5;
        }

        if (str_contains($text, '27')) {
            return 27;
        }

        if (
            str_contains($text, 'exento') ||
            str_contains($text, '0%') ||
            str_contains($text, 'no gravado')
        ) {
            return 0;
        }

        return 21;
    }
}
