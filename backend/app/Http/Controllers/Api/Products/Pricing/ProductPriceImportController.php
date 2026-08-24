<?php

namespace App\Http\Controllers\Api\Products\Pricing;

use App\Http\Controllers\Controller;
use App\Imports\ProductCatalogImport;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/** Procesa la importación de precios por bloques para evitar timeouts. */
class ProductPriceImportController extends Controller
{
    private const ANALYSIS_CHUNK_SIZE = 300;
    private const APPLY_CHUNK_SIZE = 50;

    public function simulate(Request $request)
    {
        @set_time_limit(60);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
            'identifier_type' => ['required', 'in:code,bar_code,reference_code,variant_bar_code'],
            'price_list' => ['required', 'in:a,b,c,d'],
            'price_type' => ['required', 'in:net,with_tax'],
            'round_decimals' => ['nullable', 'integer', 'min:0', 'max:4'],
            'round_direction' => ['nullable', 'in:normal,up,down'],
        ]);

        $token = Str::uuid()->toString();
        $extension = strtolower($request->file('file')->getClientOriginalExtension() ?: 'xlsx');
        $sourcePath = $request->file('file')->storeAs('imports/product-prices/source', "{$token}.{$extension}", 'local');
        $absolutePath = Storage::disk('local')->path($sourcePath);

        try {
            $worksheetInfo = IOFactory::createReaderForFile($absolutePath)->listWorksheetInfo($absolutePath);
            $totalRows = (int) ($worksheetInfo[0]['totalRows'] ?? 0);
            if ($totalRows < 2) {
                Storage::disk('local')->delete($sourcePath);
                return response()->json(['message' => 'El archivo no contiene filas de precios.'], 422);
            }

            $headerImport = new ProductCatalogImport([], [], 1, 1);
            Excel::import($headerImport, $absolutePath);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($sourcePath);
            report($exception);
            return response()->json(['message' => 'No se pudo leer el archivo de precios.'], 422);
        }

        $missingHeaders = collect(['codigo', 'precio'])
            ->reject(fn (string $header) => in_array($header, $headerImport->headers, true))
            ->values();

        if ($missingHeaders->isNotEmpty()) {
            Storage::disk('local')->delete($sourcePath);
            return response()->json([
                'message' => 'No se reconocieron las columnas obligatorias codigo y precio.',
                'missing_headers' => $missingHeaders,
                'detected_headers' => $headerImport->headers,
            ], 422);
        }

        Cache::put($this->cacheKey($token), [
            'phase' => 'analysis',
            'source_path' => $sourcePath,
            'next_row' => 2,
            'total_rows' => $totalRows,
            'headers' => $headerImport->headers,
            'header_labels' => $headerImport->headerLabels,
            'identifier_type' => $data['identifier_type'],
            'price_list' => $data['price_list'],
            'price_type' => $data['price_type'],
            'round_decimals' => (int) ($data['round_decimals'] ?? 2),
            'round_direction' => $data['round_direction'] ?? 'normal',
            'preview' => [],
            'errors' => [],
            'ignored' => [],
            'summary' => ['total_rows' => 0, 'matched' => 0, 'errors' => 0, 'ignored' => 0],
        ], now()->addHours(2));

        return response()->json([
            'token' => $token,
            'done' => false,
            'processed' => 0,
            'total' => max(0, $totalRows - 1),
            'progress' => 0,
        ]);
    }

    public function analyze(Request $request)
    {
        @set_time_limit(120);

        $data = $request->validate(['token' => ['required', 'uuid']]);
        $cacheKey = $this->cacheKey($data['token']);
        $state = Cache::get($cacheKey);

        if (! $state || ($state['phase'] ?? null) !== 'analysis'
            || ! Storage::disk('local')->exists($state['source_path'])) {
            return response()->json(['message' => 'El análisis venció o no existe. Volvé a cargar el archivo.'], 422);
        }

        $startRow = (int) $state['next_row'];
        $limit = min(self::ANALYSIS_CHUNK_SIZE, max(0, (int) $state['total_rows'] - $startRow + 1));
        $import = new ProductCatalogImport($state['headers'], $state['header_labels'], $startRow, max(1, $limit));

        try {
            Excel::import($import, Storage::disk('local')->path($state['source_path']));
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'No se pudo analizar este bloque del archivo.'], 422);
        }

        $rows = $import->rows ?? collect();
        $productLookup = $this->buildProductLookup($rows, $state['identifier_type']);

        foreach ($rows as $row) {
            $this->analyzeRow((array) $row, $state, $productLookup);
        }

        $state['next_row'] = $startRow + $limit;
        $isLast = $state['next_row'] > (int) $state['total_rows'];
        $processed = min((int) $state['total_rows'] - 1, $state['next_row'] - 2);
        $total = max(1, (int) $state['total_rows'] - 1);

        if ($isLast) {
            Storage::disk('local')->delete($state['source_path']);
            $state['phase'] = 'apply';
            $state['offset'] = 0;
            $state['processed'] = 0;
            $state['total'] = count($state['preview']);
            $state['updated'] = 0;
            unset($state['source_path'], $state['headers'], $state['header_labels'], $state['next_row'], $state['total_rows']);
            Cache::put($cacheKey, $state, now()->addHours(2));

            return response()->json([
                'token' => $data['token'],
                'done' => true,
                'processed' => $processed,
                'total' => $processed,
                'progress' => 100,
                'preview' => $state['preview'],
                'errors' => $state['errors'],
                'ignored' => $state['ignored'],
                'summary' => $state['summary'],
            ]);
        }

        Cache::put($cacheKey, $state, now()->addHours(2));

        return response()->json([
            'token' => $data['token'],
            'done' => false,
            'processed' => $processed,
            'total' => $total,
            'progress' => round($processed / $total * 100, 1),
        ]);
    }

    public function apply(Request $request)
    {
        @set_time_limit(120);

        $data = $request->validate(['token' => ['required', 'uuid']]);
        $cacheKey = $this->cacheKey($data['token']);
        $state = Cache::get($cacheKey);

        if (! $state || ($state['phase'] ?? null) !== 'apply') {
            return response()->json(['message' => 'La simulación no existe, venció o ya fue aplicada.'], 422);
        }

        if ((int) $state['total'] < 1) {
            return response()->json(['message' => 'No hay productos para actualizar.'], 422);
        }

        $items = array_slice($state['preview'], (int) $state['offset'], self::APPLY_CHUNK_SIZE);
        $updatedInChunk = 0;

        try {
            DB::transaction(function () use ($items, $state, &$updatedInChunk) {
                foreach ($items as $item) {
                    $product = Product::query()
                        ->where('is_active', true)
                        ->find($item['product_id']);
                    if (! $product) continue;

                    $field = $item['price_field'];
                    $product->{$field} = $item['new_value'];
                    $this->recalculateRelatedPrice($product, $state['price_list'], $state['price_type']);
                    $product->save();
                    $updatedInChunk++;
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'No se pudo guardar este bloque de precios. El progreso anterior se conserva.'], 422);
        }

        $state['offset'] += count($items);
        $state['processed'] += count($items);
        $state['updated'] += $updatedInChunk;
        $done = $state['processed'] >= $state['total'];

        if ($done) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $state, now()->addHours(2));
        }

        return response()->json([
            'message' => $done ? 'Precios actualizados correctamente.' : 'Bloque de precios actualizado.',
            'done' => $done,
            'processed' => min($state['processed'], $state['total']),
            'total' => $state['total'],
            'progress' => round($state['processed'] / $state['total'] * 100, 1),
            'updated' => $state['updated'],
        ]);
    }

    private function analyzeRow(array $row, array &$state, array $productLookup): void
    {
        $excelRow = (int) ($row['_source_row'] ?? 0);
        $code = trim((string) ($row['codigo'] ?? ''));
        $price = $row['precio'] ?? null;
        $state['summary']['total_rows']++;

        if ($code === '') {
            $state['errors'][] = ['row' => $excelRow, 'message' => 'La columna codigo está vacía.'];
            $state['summary']['errors']++;
            return;
        }

        if (! is_numeric($price)) {
            $state['errors'][] = ['row' => $excelRow, 'code' => $code, 'message' => 'La columna precio debe ser numérica.'];
            $state['summary']['errors']++;
            return;
        }

        $product = $productLookup[$code] ?? null;
        if (! $product) {
            $state['ignored'][] = ['row' => $excelRow, 'code' => $code, 'message' => 'Producto no encontrado.'];
            $state['summary']['ignored']++;
            return;
        }

        if (! $product->is_active) {
            $state['ignored'][] = [
                'row' => $excelRow,
                'code' => $code,
                'message' => "El producto {$product->name} está inhabilitado y no será actualizado.",
            ];
            $state['summary']['ignored']++;
            return;
        }

        $newValue = $this->roundValue((float) $price, $state['round_decimals'], $state['round_direction']);
        $priceField = $state['price_type'] === 'net'
            ? "price_{$state['price_list']}"
            : "price_{$state['price_list']}_with_tax";
        $oldValue = (float) ($product->{$priceField} ?? 0);

        $state['preview'][] = [
            'row' => $excelRow,
            'product_id' => $product->id,
            'code' => $code,
            'product_name' => $product->name,
            'price_field' => $priceField,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'difference' => $newValue - $oldValue,
            'difference_percentage' => $oldValue > 0 ? (($newValue - $oldValue) / $oldValue) * 100 : null,
        ];
        $state['summary']['matched']++;
    }

    private function cacheKey(string $token): string
    {
        return "product_price_import_{$token}";
    }

    private function buildProductLookup($rows, string $identifierType): array
    {
        $codes = $rows->pluck('codigo')
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($identifierType === 'variant_bar_code') {
            return ProductVariant::with('product')
                ->whereIn('bar_code', $codes)
                ->get()
                ->filter(fn (ProductVariant $variant) => $variant->product !== null)
                ->mapWithKeys(fn (ProductVariant $variant) => [(string) $variant->bar_code => $variant->product])
                ->all();
        }

        $field = match ($identifierType) {
            'code' => 'code',
            'bar_code' => 'bar_code',
            'reference_code' => 'reference_code',
        };

        return Product::whereIn($field, $codes)
            ->get()
            ->mapWithKeys(fn (Product $product) => [(string) $product->{$field} => $product])
            ->all();
    }

    private function roundValue(float $value, int $decimals, string $direction): float
    {
        $factor = 10 ** $decimals;
        return match ($direction) {
            'up' => ceil($value * $factor) / $factor,
            'down' => floor($value * $factor) / $factor,
            default => round($value, $decimals),
        };
    }

    private function recalculateRelatedPrice(Product $product, string $list, string $priceType): void
    {
        $tax = $this->getTaxPercentage($product->aliquot_name);
        $netField = "price_{$list}";
        $taxField = "price_{$list}_with_tax";

        if ($priceType === 'net') {
            $product->{$taxField} = round((float) $product->{$netField} * (1 + $tax / 100), 2);
            return;
        }

        $product->{$netField} = $tax > 0
            ? round((float) $product->{$taxField} / (1 + $tax / 100), 2)
            : (float) $product->{$taxField};
    }

    private function getTaxPercentage(?string $aliquotName): float
    {
        return match ($aliquotName) {
            'IVA 10,5%' => 10.5,
            'IVA 27%' => 27,
            'Exento' => 0,
            default => 21,
        };
    }
}
