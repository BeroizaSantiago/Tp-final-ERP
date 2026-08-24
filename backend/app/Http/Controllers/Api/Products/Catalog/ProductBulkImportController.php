<?php

namespace App\Http\Controllers\Api\Products\Catalog;

use App\Http\Controllers\Controller;
use App\Imports\ProductCatalogImport;
use App\Services\Products\ProductBulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ProductBulkImportController extends Controller
{
    // Los hostings compartidos suelen cortar las peticiones largas antes que PHP.
    // Bloques pequeños permiten confirmar y persistir el avance con más frecuencia.
    private const CHUNK_SIZE = 20;
    private const ANALYSIS_CHUNK_SIZE = 1500;

    public function simulate(Request $request, ProductBulkImportService $service)
    {
        @set_time_limit(60);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
            'price_mode' => ['required', 'in:net,gross'],
            'create_missing_masters' => ['nullable', 'boolean'],
        ]);

        if (! Schema::hasColumn('product_variants', 'external_id')) {
            return response()->json([
                'message' => 'Falta ejecutar la migración que agrega external_id a product_variants.',
            ], 422);
        }

        $token = Str::uuid()->toString();
        $extension = strtolower($request->file('file')->getClientOriginalExtension() ?: 'xlsx');
        $sourcePath = $request->file('file')->storeAs('imports/product-catalog/source', "{$token}.{$extension}", 'local');
        $absolutePath = Storage::disk('local')->path($sourcePath);

        $worksheetInfo = IOFactory::createReaderForFile($absolutePath)->listWorksheetInfo($absolutePath);
        $totalRows = (int) ($worksheetInfo[0]['totalRows'] ?? 0);
        if ($totalRows < 2) {
            Storage::disk('local')->delete($sourcePath);
            return response()->json(['message' => 'El archivo no contiene filas de productos.'], 422);
        }

        $import = new ProductCatalogImport([], [], 1, 1);
        Excel::import($import, $absolutePath);

        $required = collect(['nombre'])->filter(fn ($header) => ! in_array($header, $import->headers, true));
        if ($required->isNotEmpty()) {
            Storage::disk('local')->delete($sourcePath);
            return response()->json([
                'message' => 'No se reconocieron los encabezados obligatorios.',
                'missing_headers' => $required->values(),
                'detected_headers' => $import->headers,
            ], 422);
        }

        Cache::put($this->cacheKey($token), [
            'phase' => 'analysis',
            'source_path' => $sourcePath,
            'next_row' => 2,
            'total_rows' => $totalRows,
            'headers' => $import->headers,
            'header_labels' => $import->headerLabels,
            'price_mode' => $data['price_mode'],
            'create_missing_masters' => $request->boolean('create_missing_masters', true),
            'carry' => [],
            'chunks' => [],
            'result' => $this->emptySimulation(),
        ], now()->addHours(2));

        return response()->json([
            'token' => $token,
            'done' => false,
            'processed' => 0,
            'total' => max(0, $totalRows - 1),
            'progress' => 0,
        ]);
    }

    public function analyze(Request $request, ProductBulkImportService $service)
    {
        @set_time_limit(120);
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $cacheKey = $this->cacheKey($data['token']);
        $state = Cache::get($cacheKey);

        if (! $state || ($state['phase'] ?? null) !== 'analysis'
            || ! Storage::disk('local')->exists($state['source_path'])) {
            return response()->json(['message' => 'El análisis venció o no existe. Volvé a cargar el archivo.'], 422);
        }
        $simulation = $state['result'] ?? $this->emptySimulation();

        $startRow = (int) $state['next_row'];
        $limit = min(self::ANALYSIS_CHUNK_SIZE, max(0, (int) $state['total_rows'] - $startRow + 1));
        $import = new ProductCatalogImport($state['headers'], $state['header_labels'], $startRow, max(1, $limit));
        Excel::import($import, Storage::disk('local')->path($state['source_path']));

        $rows = collect($state['carry'] ?? [])->concat($import->rows ?? collect())->values();
        $state['next_row'] = $startRow + $limit;
        $isLast = $state['next_row'] > (int) $state['total_rows'];
        $state['carry'] = [];

        if (! $isLast && $rows->isNotEmpty()) {
            $lastKey = $this->rowGroupKey((array) $rows->last());
            $carry = [];
            while ($rows->isNotEmpty() && $this->rowGroupKey((array) $rows->last()) === $lastKey) {
                array_unshift($carry, $rows->pop());
            }
            $state['carry'] = $carry;
        }

        if ($rows->isNotEmpty()) {
            $chunk = $service->simulate($rows, $state['headers'], $state['header_labels'], $state['price_mode']);
            $validGroups = array_values(array_filter($chunk['groups'], fn ($group) => ! ($group['has_errors'] ?? false)));
            if ($validGroups !== []) {
                $chunkPath = "imports/product-catalog/chunks/{$data['token']}-".count($state['chunks']).'.json';
                Storage::disk('local')->put($chunkPath, json_encode(['groups' => $validGroups], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $state['chunks'][] = ['path' => $chunkPath, 'total' => count($validGroups)];
            }
            $simulation = $this->mergeSimulation($simulation, $chunk);
            $state['result'] = $simulation;
        }

        if ($isLast) {
            Storage::disk('local')->delete($state['source_path']);
            $totalValidGroups = array_sum(array_column($state['chunks'], 'total'));
            Cache::put($cacheKey, [
                'phase' => 'apply', 'chunks' => $state['chunks'], 'chunk_index' => 0, 'chunk_offset' => 0,
                'processed' => 0, 'total' => $totalValidGroups,
                'create_missing_masters' => (bool) $state['create_missing_masters'],
                'has_errors' => ($simulation['summary']['errors'] ?? 0) > 0, 'totals' => $this->emptyTotals(),
            ], now()->addHours(2));
            $processed = max(0, (int) $state['total_rows'] - 1);
            return response()->json([
                'token' => $data['token'], 'done' => true, 'processed' => $processed,
                'total' => $processed, 'progress' => 100,
            ] + $simulation);
        }

        Cache::put($cacheKey, $state, now()->addHours(2));
        $processed = min((int) $state['total_rows'] - 1, (int) $state['next_row'] - 2);
        $total = max(1, (int) $state['total_rows'] - 1);

        return response()->json([
            'token' => $data['token'], 'done' => false, 'processed' => $processed,
            'total' => $total, 'progress' => round($processed / $total * 100, 1),
        ]);
    }

    public function apply(Request $request, ProductBulkImportService $service)
    {
        @set_time_limit(120);

        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'skip_errors' => ['nullable', 'boolean'],
        ]);
        $cacheKey = $this->cacheKey($data['token']);
        $state = Cache::get($cacheKey);

        if (! $state || ($state['phase'] ?? null) !== 'apply') {
            return response()->json([
                'message' => 'La simulación venció o ya fue aplicada. Volvé a cargar el archivo.',
            ], 422);
        }

        if ($state['has_errors'] && ! $request->boolean('skip_errors')) {
            return response()->json([
                'message' => 'La importación tiene errores. Corregí el archivo o elegí importar únicamente los productos válidos.',
            ], 422);
        }

        if ($state['total'] < 1) {
            return response()->json(['message' => 'No hay productos válidos para importar.'], 422);
        }

        $chunkMeta = $state['chunks'][$state['chunk_index']] ?? null;
        if (! $chunkMeta || ! Storage::disk('local')->exists($chunkMeta['path'])) {
            return response()->json(['message' => 'Falta un bloque temporal de la importación. Volvé a cargar el archivo.'], 422);
        }

        $payload = json_decode(Storage::disk('local')->get($chunkMeta['path']), true);
        if (! is_array($payload) || ! isset($payload['groups'])) {
            return response()->json(['message' => 'El archivo temporal de importación es inválido.'], 422);
        }

        $groups = array_slice($payload['groups'], $state['chunk_offset'], self::CHUNK_SIZE);
        $chunkTotals = $this->emptyTotals();

        try {
            DB::transaction(function () use ($groups, $state, $service, &$chunkTotals) {
                foreach ($groups as $group) {
                    try {
                        $result = $service->applyGroup(
                            $group,
                            (bool) ($state['create_missing_masters'] ?? true)
                        );
                    } catch (Throwable $exception) {
                        if (str_starts_with($exception->getMessage(), 'Error en la fila ')) {
                            throw $exception;
                        }
                        $row = $group['product']['row'] ?? collect($group['rows'] ?? [])->min('_row') ?? '?';
                        throw new \RuntimeException("Error en la fila {$row}: {$exception->getMessage()}", 0, $exception);
                    }
                    foreach ($result as $key => $amount) {
                        $chunkTotals[$key] += $amount;
                    }
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception->getMessage(),
                'error' => $exception->getMessage(),
            ], 422);
        }

        foreach ($chunkTotals as $key => $amount) {
            $state['totals'][$key] += $amount;
        }
        $state['chunk_offset'] += count($groups);
        $state['processed'] += count($groups);
        if ($state['chunk_offset'] >= (int) $chunkMeta['total']) {
            Storage::disk('local')->delete($chunkMeta['path']);
            $state['chunk_index']++;
            $state['chunk_offset'] = 0;
        }
        $done = $state['processed'] >= $state['total'];

        if ($done) {
            foreach (array_slice($state['chunks'], $state['chunk_index']) as $remainingChunk) {
                Storage::disk('local')->delete($remainingChunk['path']);
            }
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $state, now()->addHours(2));
        }

        return response()->json([
            'message' => $done ? 'Catálogo importado correctamente.' : 'Bloque importado correctamente.',
            'done' => $done,
            'processed' => min($state['processed'], $state['total']),
            'total' => $state['total'],
            'progress' => $state['total'] > 0 ? round($state['processed'] / $state['total'] * 100, 1) : 100,
            'totals' => $state['totals'],
        ]);
    }

    private function cacheKey(string $token): string
    {
        return "product_catalog_import_{$token}";
    }

    private function emptyTotals(): array
    {
        return [
            'created_products' => 0,
            'updated_products' => 0,
            'skipped_products' => 0,
            'created_variants' => 0,
            'updated_variants' => 0,
            'skipped_variants' => 0,
        ];
    }

    private function emptySimulation(): array
    {
        return [
            'groups' => [], 'preview' => [], 'errors' => [], 'warnings' => [], 'stock_locations' => [],
            'summary' => [
                'total_rows' => 0, 'products' => 0, 'variants' => 0, 'new_products' => 0,
                'updated_products' => 0, 'new_variants' => 0, 'updated_variants' => 0,
                'errors' => 0, 'warnings' => 0, 'valid_products' => 0,
                'invalid_products' => 0, 'preview_limited' => false,
            ],
        ];
    }

    private function mergeSimulation(array $total, array $chunk): array
    {
        $total['preview'] = array_slice(array_merge($total['preview'], $chunk['preview']), 0, 250);
        $total['errors'] = array_slice(array_merge($total['errors'], $chunk['errors']), 0, 100);
        $total['warnings'] = array_values(array_unique(array_merge($total['warnings'], $chunk['warnings'])));
        $locations = array_merge($total['stock_locations'], $chunk['stock_locations']);
        $total['stock_locations'] = array_values(collect($locations)->unique(fn ($item) => ($item['branch_name'] ?? '').'|'.($item['warehouse_name'] ?? ''))->all());

        foreach ($total['summary'] as $key => $value) {
            if ($key === 'preview_limited') continue;
            $total['summary'][$key] += (int) ($chunk['summary'][$key] ?? 0);
        }
        $total['summary']['warnings'] = count($total['warnings']);
        $total['summary']['preview_limited'] = ($total['summary']['products'] > 250)
            || (bool) ($chunk['summary']['preview_limited'] ?? false);

        return $total;
    }

    private function rowGroupKey(array $row): string
    {
        foreach (['id_kiboo', 'producto_id_externo', 'codigo_producto', 'codigo_de_barra_principal', 'codigo_barras_principal', 'codigo_de_referencia', 'codigo_referencia'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') return $field.':'.mb_strtolower(Str::ascii($value));
        }

        return 'row:'.($row['_source_row'] ?? uniqid());
    }
}
