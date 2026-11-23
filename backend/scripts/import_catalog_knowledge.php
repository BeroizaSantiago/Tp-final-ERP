<?php

use App\Models\Products\Author;
use App\Models\Products\Product;
use App\Models\Products\Publisher;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$jsonPath = dirname(__DIR__) . '/datos.json';
$payload = json_decode(file_get_contents($jsonPath), true, flags: JSON_THROW_ON_ERROR);

$normalize = static function (?string $value): string {
    $value = Str::lower(Str::ascii(trim((string) $value)));
    $value = preg_replace('/\s+libro$/', '', $value);

    return preg_replace('/[^a-z0-9]+/', ' ', $value);
};

$join = static fn (array $values): string => implode(', ', array_filter($values, fn ($value) => $value !== null && $value !== ''));

$stats = [
    'libros_actualizados' => 0,
    'autores_actualizados' => 0,
    'editoriales_actualizadas' => 0,
    'sin_coincidencia' => [],
];

DB::transaction(function () use ($payload, $normalize, $join, $dryRun, &$stats): void {
    $authors = Author::all()->keyBy(fn (Author $author) => $normalize($author->name));
    foreach ($payload['autores'] ?? [] as $data) {
        $author = $authors->get($normalize($data['nombre'] ?? null));
        if (!$author) {
            $stats['sin_coincidencia'][] = 'Autor: ' . ($data['nombre'] ?? '(sin nombre)');
            continue;
        }

        $notes = array_filter([
            !empty($data['generos']) ? 'Géneros: ' . $data['generos'] . '.' : null,
            !empty($data['temas']) ? 'Temas: ' . $join($data['temas']) . '.' : null,
            !empty($data['obras_destacadas']) ? 'Obras destacadas: ' . $join($data['obras_destacadas']) . '.' : null,
        ]);

        $author->fill([
            'biography' => $data['biografia'] ?? $author->biography,
            'nationality' => $data['nacionalidad'] ?? $author->nationality,
            'birth_year' => $data['anio_nacimiento'] ?? $author->birth_year,
            'death_year' => $data['anio_fallecimiento'] ?? $author->death_year,
            'website' => $data['sitio_web'] ?? $author->website,
            'notes' => $notes ? implode(' ', $notes) : $author->notes,
        ]);
        if (!$dryRun) {
            $author->save();
        }
        $stats['autores_actualizados']++;
    }

    $publishers = Publisher::all()->keyBy(fn (Publisher $publisher) => $normalize($publisher->name));
    foreach ($payload['editoriales'] ?? [] as $data) {
        $publisher = $publishers->get($normalize($data['nombre'] ?? null));
        if (!$publisher) {
            $stats['sin_coincidencia'][] = 'Editorial: ' . ($data['nombre'] ?? '(sin nombre)');
            continue;
        }

        $description = array_filter([
            $data['descripcion'] ?? null,
            !empty($data['especializacion']) ? 'Especialización: ' . $data['especializacion'] . '.' : null,
            !empty($data['grupo_editorial']) ? 'Grupo editorial: ' . $data['grupo_editorial'] . '.' : null,
            $data['notas'] ?? null,
        ]);

        $publisher->fill([
            'description' => $description ? implode(' ', $description) : $publisher->description,
            'country' => $data['pais_origen'] ?? $publisher->country,
            'website' => $data['sitio_web'] ?? $publisher->website,
            'founded_year' => $data['anio_fundacion_o_antecedente'] ?? $publisher->founded_year,
        ]);
        if (!$dryRun) {
            $publisher->save();
        }
        $stats['editoriales_actualizadas']++;
    }

    $products = Product::with(['author', 'publisher'])->where('is_active', true)->get();
    foreach ($payload['libros'] ?? [] as $data) {
        $title = $normalize($data['titulo'] ?? null);
        $author = $normalize($data['autor'] ?? null);
        $publisher = $normalize($data['editorial'] ?? null);

        $candidates = $products->filter(fn (Product $product) => $normalize($product->name) === $title);
        $product = $candidates->first(fn (Product $product) =>
            $normalize($product->author?->name) === $author
            && $normalize($product->publisher?->name) === $publisher
        ) ?? $candidates->first(fn (Product $product) => $normalize($product->publisher?->name) === $publisher);

        if (!$product) {
            $stats['sin_coincidencia'][] = 'Libro: ' . ($data['titulo'] ?? '(sin título)') . ' / ' . ($data['editorial'] ?? '(sin editorial)');
            continue;
        }

        $extendedInfo = is_array($product->extended_info) ? $product->extended_info : [];
        $extendedInfo['bibliografia'] = $data;

        $notes = array_filter([
            !empty($data['titulo_original']) ? 'Título original: ' . $data['titulo_original'] . '.' : null,
            !empty($data['anio_original']) ? 'Publicación original: ' . $data['anio_original'] . '.' : null,
            !empty($data['anio_edicion']) ? 'Año de esta edición: ' . $data['anio_edicion'] . '.' : null,
            !empty($data['paginas_referencia']) ? 'Páginas de referencia: ' . $data['paginas_referencia'] . '.' : null,
            !empty($data['genero']) ? 'Género: ' . $data['genero'] . '.' : null,
            !empty($data['saga']) ? 'Saga: ' . $data['saga'] . (!empty($data['numero_saga']) ? ' #' . $data['numero_saga'] : '') . '.' : null,
            !empty($data['temas']) ? 'Temas: ' . $join($data['temas']) . '.' : null,
            !empty($data['palabras_clave']) ? 'Palabras clave: ' . $join($data['palabras_clave']) . '.' : null,
            !empty($data['edad_minima_orientativa']) ? 'Edad orientativa: desde ' . $data['edad_minima_orientativa'] . ' años.' : null,
            $data['notas'] ?? null,
        ]);

        $product->fill([
            'description' => $data['sinopsis'] ?? $product->description,
            'web_title' => $data['titulo'] ?? $product->web_title,
            'web_short_description' => $data['descripcion_breve'] ?? $product->web_short_description,
            'web_description' => $data['sinopsis'] ?? $product->web_description,
            'notes' => $notes ? implode(' ', $notes) : $product->notes,
            'category' => $data['genero'] ?? $product->category,
            'model' => $data['formato'] ?? $product->model,
            'image_url' => $data['url_portada'] ?? $product->image_url,
            'extended_info' => $extendedInfo,
            // El ISBN comercial existente identifica la edición cargada. Solo se completa si está vacío.
            'bar_code' => $product->bar_code ?: ($data['isbn_13'] ?? null),
        ]);
        if (!$dryRun) {
            $product->save();
        }
        $stats['libros_actualizados']++;
    }

    if ($dryRun) {
        DB::rollBack();
    }
});

echo json_encode([
    'modo' => $dryRun ? 'simulación' : 'importación',
    ...$stats,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
