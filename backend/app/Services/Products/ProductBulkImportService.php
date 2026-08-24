<?php

namespace App\Services\Products;

use App\Models\Products\Brand;
use App\Models\Products\Color;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use App\Models\Products\ProductVariant;
use App\Models\Products\Size;
use App\Models\Products\SizeType;
use App\Models\Stock\Branch;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class ProductBulkImportService
{
    private array $masterCache = [];

    public function simulate(Collection $rows, array $headers, array $headerLabels, string $priceMode): array
    {
        $errors = [];
        $warnings = [];
        $stockColumns = $this->stockColumns($headers, $headerLabels, $warnings);
        $groups = [];
        $productIdentifiers = [];

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['_source_row'] ?? ($index + 2));
            $row = $this->cleanRow((array) $row);
            $name = $this->value($row, ['nombre', 'name']);
            $externalId = $this->value($row, ['id_kiboo', 'producto_id_externo']);
            $productCode = $this->value($row, ['codigo_producto']);
            $mainBarcode = $this->value($row, ['codigo_de_barra_principal', 'codigo_barras_principal']);
            $reference = $this->value($row, ['codigo_de_referencia', 'codigo_referencia']);

            if ($name === '') {
                $errors[] = ['row' => $rowNumber, 'field' => 'NOMBRE', 'message' => 'El nombre del producto está vacío.'];
                continue;
            }

            $groupKey = $externalId !== ''
                ? 'external:'.$externalId
                : ($productCode !== ''
                    ? 'code:'.$this->key($productCode)
                    : ($mainBarcode !== ''
                        ? 'barcode:'.$this->key($mainBarcode)
                        : ($reference !== '' ? 'reference:'.$this->key($reference) : '')));

            if ($groupKey === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'IDENTIFICADOR',
                    'message' => 'Falta ID KIBOO, código de producto, código principal o referencia para agrupar el producto.',
                ];
                continue;
            }

            foreach (['Código principal' => $mainBarcode, 'Código de producto' => $productCode] as $label => $identifier) {
                if ($identifier === '') continue;
                $identifierKey = $label.'|'.$this->key($identifier);
                if (isset($productIdentifiers[$identifierKey]) && $productIdentifiers[$identifierKey] !== $groupKey) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => $label,
                        'message' => "{$label} {$identifier} aparece asociado a dos ID de producto diferentes.",
                    ];
                }
                $productIdentifiers[$identifierKey] = $groupKey;
            }

            $row['_row'] = $rowNumber;
            $groups[$groupKey]['key'] = $groupKey;
            $groups[$groupKey]['rows'][] = $row;
        }

        $groups = array_values($groups);
        $preview = [];
        $variantCount = 0;
        $newProducts = 0;
        $updatedProducts = 0;
        $newVariants = 0;
        $updatedVariants = 0;
        $globalVariantIdentifiers = [];

        foreach ($groups as &$group) {
            $rowsInGroup = collect($group['rows']);
            $group['product'] = $this->productPayload($rowsInGroup, $priceMode);
            $group['product']['row'] = $rowsInGroup->min('_row');
            $group['variants'] = [];

            $this->validateCommonValues($rowsInGroup, $errors);
            $variantKeys = [];

            foreach ($rowsInGroup as $row) {
                $variant = $this->variantPayload($row, $stockColumns, $priceMode);
                $variantKey = $variant['external_id'] !== ''
                    ? 'external:'.$variant['external_id']
                    : ($variant['bar_code'] !== ''
                        ? 'barcode:'.$this->key($variant['bar_code'])
                        : 'attributes:'.$this->key($variant['size']).'|'.$this->key($variant['color']));

                if (isset($variantKeys[$variantKey])) {
                    $errors[] = [
                        'row' => $row['_row'],
                        'field' => 'VARIANTE',
                        'message' => "La variante {$variantKey} está repetida dentro del mismo producto.",
                    ];
                    continue;
                }

                if (str_starts_with($variantKey, 'external:') || str_starts_with($variantKey, 'barcode:')) {
                    if (isset($globalVariantIdentifiers[$variantKey]) && $globalVariantIdentifiers[$variantKey] !== $group['key']) {
                        $errors[] = [
                            'row' => $row['_row'],
                            'field' => 'VARIANTE',
                            'message' => "La variante {$variantKey} aparece asociada a dos productos diferentes.",
                        ];
                    }
                    $globalVariantIdentifiers[$variantKey] = $group['key'];
                }

                $variantKeys[$variantKey] = true;
                $variant['key'] = $variantKey;
                $group['variants'][] = $variant;
            }

            $group['product']['has_variants'] = count($group['variants']) > 1
                || collect($group['variants'])->contains(fn ($variant) => $variant['size'] !== '' || $variant['color'] !== '');

            $existingProduct = $this->findExistingProduct($group['product'], $errors);
            if ($existingProduct && ! $existingProduct->is_active) {
                $identifier = $existingProduct->code
                    ?: $existingProduct->bar_code
                    ?: $existingProduct->external_id
                    ?: $existingProduct->id;

                $errors[] = [
                    'row' => (int) $rowsInGroup->min('_row'),
                    'field' => 'ESTADO',
                    'message' => "El producto {$existingProduct->name} ({$identifier}) está inhabilitado y será excluido de la importación.",
                ];
            }
            $productAction = $existingProduct ? 'update' : 'create';
            $productAction === 'create' ? $newProducts++ : $updatedProducts++;

            foreach ($group['variants'] as &$variant) {
                $existingVariant = $this->findExistingVariant($existingProduct, $variant, $errors);
                if ($existingVariant && (! $existingProduct || (int) $existingVariant->product_id !== (int) $existingProduct->id)) {
                    $errors[] = [
                        'row' => $variant['row'],
                        'field' => 'PRODUCTVARIANTID KIBOO',
                        'message' => "La variante externa {$variant['external_id']} ya pertenece a otro producto.",
                    ];
                }
                $variant['action'] = $existingVariant ? 'update' : 'create';
                $variant['existing_id'] = $existingVariant?->id;
                $variant['action'] === 'create' ? $newVariants++ : $updatedVariants++;
                $variantCount++;
            }
            unset($variant);

            $group['product']['action'] = $productAction;
            $group['product']['existing_id'] = $existingProduct?->id;

            $preview[] = [
                'row' => $rowsInGroup->min('_row'),
                'external_id' => $group['product']['external_id'],
                'name' => $group['product']['name'],
                'main_barcode' => $group['product']['bar_code'],
                'reference_code' => $group['product']['reference_code'],
                'action' => $productAction,
                'variants' => count($group['variants']),
                'variant_creates' => collect($group['variants'])->where('action', 'create')->count(),
                'variant_updates' => collect($group['variants'])->where('action', 'update')->count(),
            ];
        }
        unset($group);

        $this->unsupportedColumnWarnings($rows, $warnings);
        if ($rows->contains(fn ($row) => $this->value((array) $row, ['alicuota', 'alicuota_iva']) === '')) {
            $warnings[] = 'Hay productos sin ALÍCUOTA: se importarán con IVA 0% y no se agregará un 21% automáticamente.';
        }

        $errorRows = collect($errors)->pluck('row')
            ->filter(fn ($row) => $row !== null)
            ->map(fn ($row) => (int) $row)
            ->flip();
        $hasUnlocatedErrors = collect($errors)->contains(fn ($error) => ($error['row'] ?? null) === null);
        $validProducts = 0;
        $invalidProducts = 0;

        foreach ($groups as $index => &$group) {
            $group['has_errors'] = $hasUnlocatedErrors || collect($group['rows'])
                ->contains(fn ($row) => $errorRows->has((int) $row['_row']));
            $preview[$index]['has_errors'] = $group['has_errors'];
            $group['has_errors'] ? $invalidProducts++ : $validProducts++;
        }
        unset($group);

        return [
            'groups' => $groups,
            'preview' => array_slice($preview, 0, 250),
            'errors' => $errors,
            'warnings' => array_values(array_unique($warnings)),
            'stock_locations' => array_values($stockColumns),
            'summary' => [
                'total_rows' => $rows->count(),
                'products' => count($groups),
                'variants' => $variantCount,
                'new_products' => $newProducts,
                'updated_products' => $updatedProducts,
                'new_variants' => $newVariants,
                'updated_variants' => $updatedVariants,
                'errors' => count($errors),
                'warnings' => count(array_unique($warnings)),
                'valid_products' => $validProducts,
                'invalid_products' => $invalidProducts,
                'preview_limited' => count($preview) > 250,
            ],
        ];
    }

    public function applyGroup(array $group, bool $createMasters = true): array
    {
        $data = $group['product'];
        $product = $this->findExistingProduct($data);

        if ($product && ! $product->is_active) {
            $row = (int) ($data['row'] ?? 0);
            $identifier = $product->code ?: $product->bar_code ?: $product->id;

            throw new RuntimeException(
                "Error en la fila {$row} — ESTADO: el producto {$product->name} ({$identifier}) está inhabilitado y no puede importarse."
            );
        }

        $createdProduct = ! $product;

        if (! $product) {
            $product = new Product();
        }

        $masterData = $this->resolveMasters($data, $createMasters);
        $attributes = $this->productAttributes($data, $masterData, $product->exists);
        $attributes = $createdProduct ? $attributes : $this->changedAttributes($product, $attributes);
        $productChanged = $createdProduct || $attributes !== [];
        if ($productChanged) {
            $product->fill($attributes);
            $product->save();
        }

        $createdVariants = 0;
        $updatedVariants = 0;
        $skippedVariants = 0;
        $stockChanged = false;

        foreach ($group['variants'] as $variantData) {
            $variantMasters = $this->resolveVariantMasters($variantData, $data, $createMasters);
            $variant = $this->findExistingVariant($product, $variantData);
            $createdVariant = ! $variant;

            if (! $variant) {
                $variant = new ProductVariant(['product_id' => $product->id]);
            }

            if ($variant->product_id && (int) $variant->product_id !== (int) $product->id) {
                throw new RuntimeException("Error en la fila {$variantData['row']} — PRODUCTVARIANTID KIBOO: la variante externa {$variantData['external_id']} pertenece a otro producto.");
            }

            $variantAttributes = [
                'product_id' => $product->id,
                'size_id' => $variantMasters['size_id'],
                'color_id' => $variantMasters['color_id'],
                'sku' => $variantData['sku'] ?: $variantData['bar_code'],
                'bar_code' => $variantData['bar_code'] ?: null,
                'is_active' => true,
            ];
            if ($this->integerOrNull($variantData['external_id'])) {
                $variantAttributes['external_id'] = $this->integerOrNull($variantData['external_id']);
            }
            if ($variantData['price_a_with_tax'] !== null) {
                $variantAttributes['price_a_with_tax'] = $variantData['price_a_with_tax'];
            }
            $variantAttributes = $createdVariant
                ? $variantAttributes
                : $this->changedAttributes($variant, $variantAttributes);
            $variantChanged = $createdVariant || $variantAttributes !== [];
            if ($variantChanged) {
                $variant->fill($variantAttributes);
                $variant->save();
            }

            foreach ($variantData['stocks'] as $stock) {
                $variantChanged = $this->applyStock($product, $variant, $variantData, $data, $stock)
                    || $variantChanged;
            }

            if ($variantChanged && $variantData['stocks'] !== []) {
                app(StockService::class)->recalculateVariantStock($variant);
                $stockChanged = true;
            }

            if ($createdVariant) {
                $createdVariants++;
            } elseif ($variantChanged) {
                $updatedVariants++;
            } else {
                $skippedVariants++;
            }
        }

        if ($stockChanged) {
            app(StockService::class)->recalculateProductStock($product);
        }

        return [
            'created_products' => $createdProduct ? 1 : 0,
            'updated_products' => ! $createdProduct && $productChanged ? 1 : 0,
            'skipped_products' => ! $createdProduct && ! $productChanged && $updatedVariants === 0 && $createdVariants === 0 ? 1 : 0,
            'created_variants' => $createdVariants,
            'updated_variants' => $updatedVariants,
            'skipped_variants' => $skippedVariants,
        ];
    }

    private function productPayload(Collection $rows, string $priceMode): array
    {
        $value = fn (array $aliases) => $this->commonValue($rows, $aliases);
        $tax = $this->number($value(['alicuota', 'alicuota_iva'])) ?? 0.0;
        $priceA = $this->number($value(['precio_a']));
        $priceB = $this->number($value(['precio_b']));
        $cost = $this->number($value(['costo_del_producto', 'costo']));

        return [
            'external_id' => $value(['id_kiboo', 'producto_id_externo']),
            'code' => $value(['codigo_producto', 'codigo_de_barra_principal', 'codigo_de_referencia']),
            'name' => $value(['nombre', 'name']),
            'bar_code' => $value(['codigo_de_barra_principal', 'codigo_barras_principal']),
            'reference_code' => $value(['codigo_de_referencia', 'codigo_referencia']),
            'unit_measure_name' => $value(['unidad_de_medida']),
            'currency_name' => $value(['moneda']),
            'currency_symbol' => $this->currencySymbol($value(['moneda'])),
            'tax' => $tax,
            'aliquot_name' => $this->aliquotName($tax),
            'brand' => $value(['marca']),
            'model' => $value(['modelo']),
            'size_type' => $value(['tipo_de_talle', 'tipo_talle']),
            'categories' => array_values(array_filter([
                $value(['categoria']),
                $value(['sub_categoria_1']),
                $value(['sub_categoria_2']),
                $value(['sub_categoria_3']),
                $value(['sub_categoria_4']),
            ], fn ($item) => $item !== '')),
            'is_service' => $this->boolean($value(['servicio'])),
            'on_sale' => $this->booleanOrNull($value(['permite_venta'])),
            'can_move_stock' => $this->booleanOrNull($value(['mueve_stock'])),
            'allows_negative_stock' => $this->booleanOrNull($value(['stock_negativo'])),
            'is_own' => $this->booleanOrNull($value(['propio'])),
            'is_fractionated' => $this->booleanOrNull($value(['fraccionable'])),
            'cost' => $cost,
            'cost_net' => $priceMode === 'net' ? $cost : $this->net($cost, $tax),
            'cost_with_tax' => $priceMode === 'gross' ? $cost : $this->gross($cost, $tax),
            'discount1' => $this->number($value(['descuento_1'])),
            'discount2' => $this->number($value(['descuento_2'])),
            'discount3' => $this->number($value(['descuento_3'])),
            'price_a' => $priceMode === 'net' ? $priceA : $this->net($priceA, $tax),
            'price_a_with_tax' => $priceMode === 'gross' ? $priceA : $this->gross($priceA, $tax),
            'markup_a' => $this->number($value(['markup_a'])),
            'price_b' => $priceMode === 'net' ? $priceB : $this->net($priceB, $tax),
            'price_b_with_tax' => $priceMode === 'gross' ? $priceB : $this->gross($priceB, $tax),
            'markup_b' => $this->number($value(['markup_b'])),
            'min_stock' => $this->number($value(['stock_minimo'])),
            'reposition_stock' => $this->number($value(['stock_de_reposicion'])),
            'purchase_min_amount' => $this->number($value(['cant_min_de_compra', 'cantidad_minima_de_compra'])),
            'description' => $value(['descripcion']),
            'notes' => $value(['nota', 'notas']),
            'web_title' => $value(['web_titulo']),
            'web_short_description' => $value(['web_descripcion_corta']),
            'web_description' => $value(['web_descripcion']),
            'is_web_enabled' => $this->booleanOrNull($value(['web_habilitado', 'ver_en_ecommerce'])),
            'weight' => $this->number($value(['web_peso'])),
            'height' => $this->number($value(['web_alto'])),
            'width' => $this->number($value(['web_ancho'])),
            'length' => $this->number($value(['web_largo'])),
            'extended_info' => $this->extendedInfo($value(['web_info_extendida']), $value(['wooecommerce_ecommerce_nro_637384518'])),
            'principal_provider_name' => $value(['proveedor']),
        ];
    }

    private function variantPayload(array $row, array $stockColumns, string $priceMode): array
    {
        $tax = $this->number($this->value($row, ['alicuota', 'alicuota_iva'])) ?? 0.0;
        $priceA = $this->number($this->value($row, ['precio_a']));
        $stocks = [];

        foreach ($stockColumns as $column => $location) {
            $raw = trim((string) ($row[$column] ?? ''));
            if ($raw === '') {
                continue;
            }
            $stocks[] = $location + ['quantity' => $this->number($raw) ?? 0];
        }

        return [
            'row' => $row['_row'],
            'external_id' => $this->value($row, ['productvariantid_kiboo', 'product_variant_id_kiboo', 'codigo_variante']),
            'bar_code' => $this->value($row, ['codigo_de_barra', 'codigo_barras_variante']),
            'sku' => $this->value($row, ['codigo_variante', 'codigo_de_barra']),
            'size' => $this->value($row, ['talle']),
            'color' => $this->value($row, ['color']),
            'price_a_with_tax' => $priceMode === 'gross' ? $priceA : $this->gross($priceA, $tax),
            'stocks' => $stocks,
        ];
    }

    private function productAttributes(array $data, array $masters, bool $existing): array
    {
        $attributes = [
            'name' => $data['name'],
            'external_id' => $this->integerOrNull($data['external_id']),
            'code' => $data['code'] ?: null,
            'bar_code' => $data['bar_code'] ?: null,
            'reference_code' => $data['reference_code'] ?: null,
            'currency_name' => $data['currency_name'] ?: null,
            'currency_symbol' => $data['currency_symbol'],
            'product_type_name' => $data['is_service'] ? 'Servicio' : 'Producto',
            'aliquot_name' => $data['aliquot_name'],
            'brand' => $data['brand'] ?: null,
            'model' => $data['model'] ?: null,
            'category' => collect($data['categories'])->last(),
            'unit_measure_name' => $data['unit_measure_name'] ?: null,
            'size_type_name' => $data['size_type'] ?: null,
            'category_id' => $masters['category_id'],
            'brand_id' => $masters['brand_id'],
            'product_model_id' => $masters['model_id'],
            'has_variants' => $data['has_variants'],
            'on_sale' => $data['on_sale'],
            'can_move_stock' => $data['can_move_stock'] ?? ! $data['is_service'],
            'allows_negative_stock' => $data['allows_negative_stock'],
            'is_own' => $data['is_own'],
            'is_fractionated' => $data['is_fractionated'],
            'cost_with_discount' => $data['cost_net'],
            'cost_with_discount_with_tax_aliquot' => $data['cost_with_tax'],
            'discount1' => $data['discount1'],
            'discount2' => $data['discount2'],
            'discount3' => $data['discount3'],
            'price_a' => $data['price_a'],
            'price_a_with_tax' => $data['price_a_with_tax'],
            'markup_a' => $data['markup_a'],
            'price_b' => $data['price_b'],
            'price_b_with_tax' => $data['price_b_with_tax'],
            'markup_b' => $data['markup_b'],
            'min_stock' => $data['min_stock'],
            'reposition_stock' => $data['reposition_stock'],
            'purchase_min_amount' => $data['purchase_min_amount'],
            'description' => $data['description'] ?: null,
            'notes' => $data['notes'] ?: null,
            'web_title' => $data['web_title'] ?: null,
            'web_short_description' => $data['web_short_description'] ?: null,
            'web_description' => $data['web_description'] ?: null,
            'is_web_enabled' => $data['is_web_enabled'],
            'weight' => $data['weight'],
            'height' => $data['height'],
            'width' => $data['width'],
            'length' => $data['length'],
            'extended_info' => $data['extended_info'],
            'principal_provider_name' => $data['principal_provider_name'] ?: null,
            'is_active' => true,
        ];

        return array_filter($attributes, fn ($value, $key) =>
            $value !== null && ($value !== '' || in_array($key, ['name'], true)), ARRAY_FILTER_USE_BOTH);
    }

    private function resolveMasters(array $data, bool $create): array
    {
        $brand = $this->master(Brand::class, $data['brand'], [], $create);
        $model = $this->master(ProductModel::class, $data['model'], ['brand_id' => $brand?->id], $create);
        $parentId = null;
        $category = null;

        foreach ($data['categories'] as $name) {
            $category = $this->master(ProductCategory::class, $name, ['parent_id' => $parentId], $create);
            $parentId = $category?->id;
        }

        return ['brand_id' => $brand?->id, 'model_id' => $model?->id, 'category_id' => $category?->id];
    }

    private function resolveVariantMasters(array $variant, array $product, bool $create): array
    {
        $sizeType = $this->master(SizeType::class, $product['size_type'], [], $create);
        $size = $this->master(Size::class, $variant['size'], ['size_type_id' => $sizeType?->id], $create);
        $color = $this->master(Color::class, $variant['color'], [], $create);

        return ['size_id' => $size?->id, 'color_id' => $color?->id];
    }

    private function master(string $modelClass, string $name, array $scope, bool $create)
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $cacheKey = $modelClass.'|'.json_encode($scope).'|'.$this->key($name);
        if (array_key_exists($cacheKey, $this->masterCache)) {
            return $this->masterCache[$cacheKey];
        }

        $query = $modelClass::query();
        if ($scope !== []) {
            $query->where($scope);
        }
        $record = $query->get()->first(fn ($item) => $this->key($item->name) === $this->key($name));

        if (! $record && $create) {
            $record = $modelClass::create($scope + ['name' => $name, 'is_active' => true]);
        }

        if (! $record && ! $create) {
            throw new RuntimeException("No existe el maestro {$name} y se desactivó su creación automática.");
        }

        return $this->masterCache[$cacheKey] = $record;
    }

    private function applyStock(Product $product, ProductVariant $variant, array $variantData, array $productData, array $stock): bool
    {
        $branch = $this->master(Branch::class, $stock['branch_name'], [], true);
        $this->master(Warehouse::class, $stock['warehouse_name'], ['branch_id' => $branch->id], true);

        $variant->loadMissing(['size', 'color']);
        $cost = (float) ($product->cost_with_discount ?? 0);
        $quantity = (float) $stock['quantity'];

        $inventoryItem = InventoryItem::firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'branch_name' => $stock['branch_name'],
            'warehouse_name' => $stock['warehouse_name'],
        ]);
        $inventoryAttributes = [
            'product_external_id' => $product->external_id,
            'product_variant_external_id' => $variant->external_id,
            'code' => $this->integerOrNull($product->external_id),
            'bar_code' => $variant->bar_code ?: $product->bar_code,
            'reference_code' => $product->reference_code,
            'product_name' => $product->name,
            'min_stock' => $productData['min_stock'] ?? 0,
            'reposition_stock' => $productData['reposition_stock'] ?? 0,
            'current_stock' => $quantity,
            'color_name' => $variant->color?->name,
            'size_name' => $variant->size?->name,
            'valued_item' => round($quantity * $cost, 4),
            'currency_symbol' => $product->currency_symbol ?: '$',
        ];

        if ($inventoryItem->exists) {
            $inventoryAttributes = $this->changedAttributes($inventoryItem, $inventoryAttributes);
        }

        if (! $inventoryItem->exists || $inventoryAttributes !== []) {
            $inventoryItem->fill($inventoryAttributes);
            $inventoryItem->save();
            return true;
        }

        return false;
    }

    private function changedAttributes($model, array $attributes): array
    {
        return array_filter($attributes, function ($value, $key) use ($model) {
            $current = $model->getAttribute($key);

            if ($current === null || $value === null) {
                return $current !== $value;
            }

            if (is_numeric($current) && is_numeric($value)) {
                return abs((float) $current - (float) $value) > 0.00001;
            }

            if (is_bool($current) || is_bool($value)) {
                return (bool) $current !== (bool) $value;
            }

            if (is_array($current) || is_array($value)) {
                return json_encode($current) !== json_encode($value);
            }

            return (string) $current !== (string) $value;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function findExistingProduct(array $data, array &$errors = []): ?Product
    {
        $externalId = $this->integerOrNull($data['external_id']);
        if ($externalId) {
            $externalMatch = Product::query()->where('external_id', $externalId)->first();
            if ($externalMatch) return $externalMatch;
        }

        $queries = [];
        if ($data['bar_code'] !== '') {
            $queries['código principal'] = ['bar_code', $data['bar_code']];
        } elseif (($data['code'] ?? '') !== '') {
            $queries['código'] = ['code', $data['code']];
        } elseif ($data['reference_code'] !== '') {
            $queries['referencia'] = ['reference_code', $data['reference_code']];
        }

        $found = collect();
        foreach ($queries as $label => [$field, $value]) {
            $matches = Product::query()->where($field, $value)->limit(2)->get();
            if ($matches->count() > 1) {
                $errors[] = ['row' => $data['row'] ?? null, 'field' => $label, 'message' => "Hay más de un producto con el mismo {$label}: {$value}."];
                return null;
            }
            if ($matches->isNotEmpty()) $found->push($matches->first());
        }

        if ($found->pluck('id')->unique()->count() > 1) {
            $errors[] = [
                'row' => $data['row'] ?? null,
                'field' => 'IDENTIFICADORES',
                'message' => "Los identificadores del producto {$data['name']} coinciden con productos locales diferentes.",
            ];
            return null;
        }

        return $found->first();
    }

    private function findExistingVariant(?Product $product, array $data, array &$errors = []): ?ProductVariant
    {
        $externalId = $this->integerOrNull($data['external_id']);
        if ($externalId) {
            return ProductVariant::query()->where('external_id', $externalId)->first();
        }

        if ($data['bar_code'] !== '') {
            $matches = ProductVariant::query()->where('bar_code', $data['bar_code'])->limit(2)->get();
            if ($matches->count() > 1) {
                $errors[] = ['row' => $data['row'] ?? null, 'field' => 'CÓDIGO DE BARRA', 'message' => "Código de barras de variante duplicado: {$data['bar_code']}."];
                return null;
            }
            if ($matches->isNotEmpty()) return $matches->first();
        }

        if (! $product) return null;
        $sizeId = $data['size'] !== '' ? $this->namedId(Size::class, $data['size']) : null;
        $colorId = $data['color'] !== '' ? $this->namedId(Color::class, $data['color']) : null;
        if (($data['size'] !== '' && ! $sizeId) || ($data['color'] !== '' && ! $colorId)) {
            return null;
        }

        return $product->variants()
            ->when($sizeId, fn ($query) => $query->where('size_id', $sizeId), fn ($query) => $query->whereNull('size_id'))
            ->when($colorId, fn ($query) => $query->where('color_id', $colorId), fn ($query) => $query->whereNull('color_id'))
            ->first();
    }

    private function namedId(string $modelClass, string $name): ?int
    {
        return $modelClass::query()->get()->first(fn ($item) => $this->key($item->name) === $this->key($name))?->id;
    }

    private function validateCommonValues(Collection $rows, array &$errors): void
    {
        foreach ([
            ['nombre'], ['codigo_de_barra_principal'], ['codigo_de_referencia'], ['moneda'], ['alicuota'],
            ['marca'], ['modelo'], ['categoria'], ['sub_categoria_1'], ['sub_categoria_2'],
            ['sub_categoria_3'], ['sub_categoria_4'], ['costo_del_producto'], ['precio_b'],
        ] as $aliases) {
            $values = $rows->map(fn ($row) => $this->value($row, $aliases))->filter()->map(fn ($value) => $this->key($value))->unique();
            if ($values->count() > 1) {
                $errors[] = [
                    'row' => $rows->min('_row'),
                    'field' => strtoupper(str_replace('_', ' ', $aliases[0])),
                    'message' => 'Las variantes del producto tienen valores generales diferentes en '.str_replace('_', ' ', $aliases[0]).'.',
                ];
            }
        }
    }

    private function stockColumns(array $headers, array $headerLabels, array &$warnings): array
    {
        $result = [];
        foreach ($headers as $header) {
            if (! str_starts_with($header, 'stock_')) continue;
            if (in_array($header, ['stock_negativo', 'stock_minimo', 'stock_de_reposicion'], true)) continue;
            $label = $headerLabels[$header] ?? $header;
            if (preg_match('/^\s*STOCK\s*-\s*(?:DEPOS|DEP[ÓO]SITO)\s*:\s*(.*?)\s*-\s*(?:SUR|SUCURSAL)\s*:\s*(.*?)\s*$/iu', $label, $match)) {
                $result[$header] = [
                    'column' => $header,
                    'warehouse_name' => trim($match[1]),
                    'branch_name' => trim($match[2]),
                ];
            } else {
                $warnings[] = "No se pudo interpretar la ubicación de la columna {$label}; su stock no será importado.";
            }
        }
        return $result;
    }

    private function unsupportedColumnWarnings(Collection $rows, array &$warnings): void
    {
        $unsupported = [
            'descuento_4' => 'Descuento 4',
            'web_destacado' => 'Web destacado',
            'web_promocion' => 'Web promoción',
            'cod_proveedor' => 'Código de proveedor',
            'precio_c' => 'Precio C',
            'markup_c' => 'Markup C',
            'precio_d' => 'Precio D',
            'markup_d' => 'Markup D',
        ];
        foreach ($unsupported as $column => $label) {
            if ($rows->contains(fn ($row) => trim((string) ($row[$column] ?? '')) !== '')) {
                $warnings[] = "{$label} contiene datos pero todavía no tiene un campo equivalente y será omitido.";
            }
        }
    }

    private function commonValue(Collection $rows, array $aliases): string
    {
        foreach ($rows as $row) {
            $value = $this->value($row, $aliases);
            if ($value !== '') return $value;
        }
        return '';
    }

    private function value(array $row, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row) && trim((string) $row[$alias]) !== '') {
                return trim((string) $row[$alias]);
            }
        }
        return '';
    }

    private function cleanRow(array $row): array
    {
        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
    }

    private function booleanOrNull(string $value): ?bool
    {
        return $value === '' ? null : $this->boolean($value);
    }

    private function boolean(string $value): bool
    {
        return in_array($this->key($value), ['s', 'si', 'yes', 'y', '1', 'true', 'verdadero'], true);
    }

    private function number($value): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;
        if (is_numeric($value)) return (float) $value;
        $number = preg_replace('/[^0-9,.-]/', '', (string) $value);
        if (str_contains($number, ',') && str_contains($number, '.')) {
            $number = strrpos($number, ',') > strrpos($number, '.')
                ? str_replace(',', '.', str_replace('.', '', $number))
                : str_replace(',', '', $number);
        } elseif (str_contains($number, ',')) {
            $number = str_replace(',', '.', $number);
        }
        return is_numeric($number) ? (float) $number : null;
    }

    private function net(?float $gross, float $tax): ?float
    {
        return $gross === null ? null : round($gross / (1 + $tax / 100), 4);
    }

    private function gross(?float $net, float $tax): ?float
    {
        return $net === null ? null : round($net * (1 + $tax / 100), 4);
    }

    private function aliquotName(float $tax): string
    {
        if ($tax == 0.0) return 'Exento';
        return 'IVA '.str_replace('.', ',', rtrim(rtrim(number_format($tax, 2, '.', ''), '0'), '.')).'%';
    }

    private function currencySymbol(string $currency): string
    {
        $currency = $this->key($currency);
        return str_contains($currency, 'dolar') || str_contains($currency, 'usd') ? 'US$' : '$';
    }

    private function extendedInfo(string $info, string $woocommerceId): ?array
    {
        $result = [];
        if ($info !== '') {
            $decoded = json_decode($info, true);
            $result = is_array($decoded) ? $decoded : ['source_info' => $info];
        }
        if ($woocommerceId !== '') $result['woocommerce_id'] = $woocommerceId;
        return $result ?: null;
    }

    private function integerOrNull($value): ?int
    {
        $value = trim((string) $value);
        return ctype_digit($value) ? (int) $value : null;
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', Str::ascii($value))));
    }

    private function title(string $value): string
    {
        return mb_convert_case(str_replace('_', ' ', trim($value, '_')), MB_CASE_TITLE, 'UTF-8');
    }
}
