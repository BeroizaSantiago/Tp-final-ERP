<?php

namespace App\Services\IA;

use App\Models\IA\AiEnrichment;
use App\Models\Clients\Client;
use App\Models\Products\Author;
use App\Models\Products\Collection;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\Publisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Arma el contexto del catálogo que se le pasa a Gemini.
 *
 * La idea es no mandar toda la base: se seleccionan las fichas relevantes,
 * se recortan los textos largos y se incluyen solo datos confirmados y fichas
 * aprobadas. Así el modelo tiene información útil sin gastar tokens de más.
 */
class CatalogContextBuilder
{
    private const MAX_SEARCH_RESULTS = 8;

    private const MAX_TEXT_LENGTH = 1600;

    private const MAX_SEARCH_TEXT_LENGTH = 700;

    /**
     * Contexto para una consulta sobre un libro concreto.
     */
    public function forProduct(Product $product): array
    {
        $product->loadMissing([
            'author',
            'category',
            'publisher',
            'collection',
        ]);

        $otrosLibros = [];

        if ($product->author) {
            $otrosLibros = $product->author->products()
                ->whereKeyNot($product->getKey())
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(5)
                ->pluck('name')
                ->all();
        }

        return $this->clean([
            'libro' => $this->productData($product, withEnrichments: true),
            'otros_libros_del_autor' => $otrosLibros,
        ]);
    }

    /**
     * Contexto para una consulta sobre un autor.
     */
    public function forAuthor(Author $author): array
    {
        $libros = $author->products()
            ->where('is_active', true)
            ->with([
                'category:id,name,description',
                'publisher:id,name,description,country,website',
                'collection:id,name,description',
            ])
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $this->clean([
            'autor' => $this->authorData($author, withEnrichments: true),
            'libros' => $libros
                ->map(fn (Product $product) => $this->productSummary($product))
                ->values()
                ->all(),
        ]);
    }

    /** Contexto comercial anonimizado para recomendar libros a un cliente. */
    public function forClientRecommendations(Client $client): array
    {
        $invoices = $client->invoices()
            ->with(['items.product.author', 'items.product.category', 'items.product.publisher', 'items.product.collection'])
            ->whereRaw("LOWER(COALESCE(status_name, '')) NOT LIKE '%anul%'")
            ->where(function ($query) {
                $query->where('receipt_types_prefix', 'FV')->orWhere(function ($legacy) {
                    $legacy->whereNull('receipt_types_prefix')
                        ->whereRaw("LOWER(COALESCE(receipt_type_name, '')) LIKE '%factura%'")
                        ->whereRaw("LOWER(COALESCE(receipt_type_name, '')) NOT LIKE '%compra%'");
                });
            })->orderByDesc('issue_date')->limit(100)->get();

        $items = $invoices->flatMap(fn ($invoice) => $invoice->items->map(function ($item) use ($invoice) {
            $product = $item->product;
            return ['product_id'=>$product?->id, 'titulo'=>$item->product_name ?: $product?->name ?: $item->description,
                'cantidad'=>(float)$item->quantity, 'fecha'=>optional($invoice->issue_date)->format('Y-m-d'),
                'autor_id'=>$product?->author_id, 'autor'=>$product?->author?->name,
                'genero_id'=>$product?->category_id, 'genero'=>$product?->getRelation('category')?->name ?: $product?->getAttribute('category'),
                'editorial_id'=>$product?->publisher_id, 'editorial'=>$product?->publisher?->name,
                'coleccion_id'=>$product?->collection_id, 'coleccion'=>$product?->collection?->name];
        }))->filter(fn ($item) => !empty($item['titulo']))->values();

        $history = $items->groupBy(fn ($item) => $item['product_id'] ?: Str::lower($item['titulo']))->map(function ($group) {
            $first=$group->first();
            return $this->clean(['product_id'=>$first['product_id'], 'titulo'=>$first['titulo'],
                'cantidad_total'=>round((float)$group->sum('cantidad'),2), 'ultima_compra'=>$group->max('fecha'),
                'autor'=>$first['autor'], 'genero'=>$first['genero'], 'editorial'=>$first['editorial'], 'coleccion'=>$first['coleccion']]);
        })->sortByDesc('ultima_compra')->values();

        $preferences = ['author_id'=>$items->pluck('autor_id')->filter()->countBy()->sortDesc(),
            'category_id'=>$items->pluck('genero_id')->filter()->countBy()->sortDesc(),
            'publisher_id'=>$items->pluck('editorial_id')->filter()->countBy()->sortDesc(),
            'collection_id'=>$items->pluck('coleccion_id')->filter()->countBy()->sortDesc()];
        $purchasedIds=$items->pluck('product_id')->filter()->unique();
        $purchasedNames=$items->pluck('titulo')->filter()->map(fn($name)=>Str::lower(trim($name)))->unique();

        $candidates=Product::query()->with(['author','category','publisher','collection'])->where('is_active',true)
            ->whereNotIn('id',$purchasedIds)->where(function($query){$query->where('available_stock','>',0)->orWhere('current_stock','>',0)->orWhereHas('variants',fn($variant)=>$variant->where('current_stock','>',0));})
            ->orderBy('name')->get()->reject(fn(Product $product)=>$purchasedNames->contains(Str::lower(trim($product->name))))
            ->map(function(Product $product)use($preferences){$score=0;foreach($preferences as $field=>$counts){$value=$product->{$field};if($value&&$counts->has($value))$score+=(int)$counts->get($value);}return['score'=>$score,'product'=>$product];})
            ->sortByDesc('score')->take(15)->values();

        $labels=static function($counts,string $model):array{if($counts->isEmpty())return[];$names=$model::query()->whereIn('id',$counts->keys())->pluck('name','id');return$counts->take(5)->map(fn($count,$id)=>['nombre'=>$names->get($id),'compras'=>$count])->values()->all();};
        return $this->clean(['cliente'=>['id'=>$client->id,'nombre'=>$client->name], 'historial_compras'=>$history->take(40)->all(),
            'preferencias_inferidas'=>['autores'=>$labels($preferences['author_id'],Author::class),'generos'=>$labels($preferences['category_id'],ProductCategory::class),'editoriales'=>$labels($preferences['publisher_id'],Publisher::class),'colecciones'=>$labels($preferences['collection_id'],Collection::class)],
            'candidatos_con_stock'=>$candidates->map(fn($entry)=>[...$this->productData($entry['product'],withEnrichments:true),'afinidad'=>$entry['score']])->all()]);
    }

    /**
     * Busca fichas relacionadas con la consulta libre del usuario.
     *
     * No pretende ser un buscador semántico: alcanza para que una pregunta como
     * "libros sobre novela histórica" llegue con libros, autores y editoriales
     * relacionados en lugar de un contexto vacío.
     */
    public function search(string $query, int $limit = self::MAX_SEARCH_RESULTS): array
    {
        $terms = $this->searchTerms($query);

        if ($terms === []) {
            return [];
        }

        $products = Product::query()
            ->with([
                'author:id,name,biography,nationality,birth_year,death_year,website,notes',
                'category:id,name,description',
                'publisher:id,name,description,country,website,founded_year',
                'collection:id,name,description',
            ])
            ->where('is_active', true)
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $like = $this->like($term);

                    $builder
                        ->orWhere('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhere('web_title', 'like', $like)
                        ->orWhere('web_short_description', 'like', $like)
                        ->orWhere('web_description', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('bar_code', 'like', $like)
                        ->orWhere('reference_code', 'like', $like)
                        ->orWhereHas('author', function ($authorQuery) use ($like) {
                            $authorQuery
                                ->where('name', 'like', $like)
                                ->orWhere('biography', 'like', $like)
                                ->orWhere('nationality', 'like', $like)
                                ->orWhere('notes', 'like', $like);
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($like) {
                            $categoryQuery
                                ->where('name', 'like', $like)
                                ->orWhere('description', 'like', $like);
                        })
                        ->orWhereHas('publisher', function ($publisherQuery) use ($like) {
                            $publisherQuery
                                ->where('name', 'like', $like)
                                ->orWhere('description', 'like', $like)
                                ->orWhere('country', 'like', $like);
                        })
                        ->orWhereHas('collection', function ($collectionQuery) use ($like) {
                            $collectionQuery
                                ->where('name', 'like', $like)
                                ->orWhere('description', 'like', $like);
                        });
                }
            })
            ->limit(max(32, $limit * 6))
            ->get()
            ->sortByDesc(fn (Product $product) => $this->relevanceScore($product, $terms))
            ->take($limit)
            ->values();

        $authors = Author::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $like = $this->like($term);

                    $builder
                        ->orWhere('name', 'like', $like)
                        ->orWhere('biography', 'like', $like)
                        ->orWhere('nationality', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(3)
            ->get();

        $publishers = Publisher::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $like = $this->like($term);

                    $builder
                        ->orWhere('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('country', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(3)
            ->get();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $like = $this->like($term);

                    $builder
                        ->orWhere('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(3)
            ->get();

        return $this->clean([
            'resultados_catalogo' => $products
                ->map(fn (Product $product) => $this->productData($product, withEnrichments: true))
                ->values()
                ->all(),
            'autores' => $authors
                ->map(fn (Author $author) => $this->authorData($author, withEnrichments: true))
                ->values()
                ->all(),
            'editoriales' => $publishers
                ->map(fn (Publisher $publisher) => $this->publisherData($publisher, withEnrichments: true))
                ->values()
                ->all(),
            'categorias' => $categories
                ->map(fn (ProductCategory $category) => $this->categoryData($category, withEnrichments: true))
                ->values()
                ->all(),
        ]);
    }

    /** Resuelve localmente preguntas cuantitativas o listados del catálogo. */
    public function localCatalogAnswer(string $query): ?string
    {
        $normalized = Str::lower(Str::ascii($query));
        $products = Product::query()->with(['author','category','publisher','collection'])->where('is_active', true)->get();
        $categories = ProductCategory::query()->get()->filter(fn ($category) => str_contains($normalized, Str::lower(Str::ascii($category->name))));

        if ($categories->isNotEmpty()) {
            $ids = $categories->pluck('id');
            $names = $categories->pluck('name')->map(fn ($name) => Str::lower(Str::ascii($name)));
            $products = $products->filter(fn (Product $product) => $ids->contains($product->category_id)
                || $names->contains(Str::lower(Str::ascii((string) $product->getAttribute('category')))));
        } else {
            $authors = Author::query()->get()->filter(fn ($author) => str_contains($normalized, Str::lower(Str::ascii($author->name))));
            $publishers = Publisher::query()->get()->filter(fn ($publisher) => str_contains($normalized, Str::lower(Str::ascii($publisher->name))));
            if ($authors->isNotEmpty()) $products = $products->whereIn('author_id', $authors->pluck('id'));
            if ($publishers->isNotEmpty()) $products = $products->whereIn('publisher_id', $publishers->pluck('id'));
        }

        if ($products->isEmpty()) return 'No se encontraron libros del catálogo que coincidan con la consulta.';
        if (!preg_match('/cu[aá]nt|cantidad|inventario|stock|tenemos|disponib/i', $query)) return null;

        $units = $products->sum(fn (Product $product) => (float) ($product->available_stock ?: $product->current_stock));
        $detail = $products->sortBy('name')->map(function (Product $product) {
            $stock = (float) ($product->available_stock ?: $product->current_stock);
            return $product->name.' ('.rtrim(rtrim(number_format($stock, 2, ',', '.'), '0'), ',').' unidades)';
        })->implode(', ');

        return 'Hay '.$products->count().' títulos que coinciden con la consulta, con '.$this->plainNumber($units).' unidades en total: '.$detail.'.';
    }

    private function plainNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    /**
     * Datos públicos de un autor. Se expone para poder reutilizarlo cuando la
     * consulta pide un autor pero no es del tipo "resumen_autor".
     */
    public function authorData(?Author $author, bool $withEnrichments = false): ?array
    {
        if (!$author) {
            return null;
        }

        $data = [
            'id' => $author->id,
            'nombre' => $author->name,
            'biografia' => $this->text($author->biography),
            'nacionalidad' => $author->nationality,
            'anio_nacimiento' => $author->birth_year,
            'anio_fallecimiento' => $author->death_year,
            'sitio_web' => $author->website,
            'notas' => $this->text($author->notes, 800),
        ];

        if ($withEnrichments) {
            $data['fichas_aprobadas'] = $this->enrichmentsFor($author);
        }

        return $this->clean($data);
    }

    private function productData(Product $product, bool $withEnrichments = false): array
    {
        $data = [
            'id' => $product->id,
            'nombre' => $product->name,
            'codigo' => $product->code,
            'codigo_barras' => $product->bar_code,
            'codigo_referencia' => $product->reference_code,
            'stock_actual' => (float) $product->current_stock,
            'stock_disponible' => (float) $product->available_stock,
            'titulo_web' => $product->web_title,
            'descripcion' => $this->text($product->description),
            'descripcion_corta_web' => $this->text($product->web_short_description),
            'descripcion_web' => $this->text($product->web_description),
            'notas' => $this->text($product->notes, 800),
            'informacion_bibliografica' => $product->extended_info['bibliografia'] ?? null,
            'autor' => $this->authorData($product->author, $withEnrichments),
            'categoria' => $this->categoryData($product->getRelation('category'), $withEnrichments),
            'editorial' => $this->publisherData($product->publisher, $withEnrichments),
            'coleccion' => $this->collectionData($product->collection, $withEnrichments),
        ];

        if ($withEnrichments) {
            $data['fichas_aprobadas'] = $this->enrichmentsFor($product);
        }

        return $this->clean($data);
    }

    private function productSummary(Product $product): array
    {
        return $this->clean([
            'id' => $product->id,
            'nombre' => $product->name,
            'codigo' => $product->code,
            'descripcion' => $this->text($product->description, self::MAX_SEARCH_TEXT_LENGTH),
            'categoria' => $product->getRelation('category')?->name,
            'editorial' => $product->publisher?->name,
            'coleccion' => $product->collection?->name,
            'fichas_aprobadas' => $this->enrichmentsFor($product),
        ]);
    }

    private function publisherData(?Publisher $publisher, bool $withEnrichments = false): ?array
    {
        if (!$publisher) {
            return null;
        }

        $data = [
            'id' => $publisher->id,
            'nombre' => $publisher->name,
            'descripcion' => $this->text($publisher->description, 1200),
            'pais' => $publisher->country,
            'sitio_web' => $publisher->website,
            'anio_fundacion' => $publisher->founded_year,
        ];

        if ($withEnrichments) {
            $data['fichas_aprobadas'] = $this->enrichmentsFor($publisher);
        }

        return $this->clean($data);
    }

    private function collectionData(?Collection $collection, bool $withEnrichments = false): ?array
    {
        if (!$collection) {
            return null;
        }

        $data = [
            'id' => $collection->id,
            'nombre' => $collection->name,
            'descripcion' => $this->text($collection->description, 1000),
        ];

        if ($withEnrichments) {
            $data['fichas_aprobadas'] = $this->enrichmentsFor($collection);
        }

        return $this->clean($data);
    }

    private function categoryData(?ProductCategory $category, bool $withEnrichments = false): ?array
    {
        if (!$category) {
            return null;
        }

        $data = [
            'id' => $category->id,
            'nombre' => $category->name,
            'descripcion' => $this->text($category->description, 1000),
        ];

        if ($withEnrichments) {
            $data['fichas_aprobadas'] = $this->enrichmentsFor($category);
        }

        return $this->clean($data);
    }

    /**
     * Puntaje sencillo para ordenar los candidatos encontrados por SQL.
     * Título e ISBN pesan más que textos descriptivos o datos relacionados.
     */
    private function relevanceScore(Product $product, array $terms): int
    {
        $category = $product->getRelation('category');
        $publisher = $product->getRelation('publisher');
        $collection = $product->getRelation('collection');
        $fields = [
            12 => [$product->name, $product->code, $product->bar_code, $product->reference_code],
            8 => [$product->author?->name],
            5 => [$category?->name, $publisher?->name, $collection?->name],
            2 => [
                $product->description,
                $product->notes,
                $product->web_title,
                $product->web_short_description,
                $product->web_description,
                $product->author?->biography,
                $publisher?->description,
                $category?->description,
            ],
        ];
        $score = 0;

        foreach ($terms as $term) {
            $needle = Str::lower(Str::ascii($term));

            foreach ($fields as $weight => $values) {
                foreach ($values as $value) {
                    if ($value && str_contains(Str::lower(Str::ascii((string) $value)), $needle)) {
                        $score += $weight;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Fichas aprobadas para una entidad. Se toma la más reciente de cada tipo.
     */
    private function enrichmentsFor(Model $model): array
    {
        return AiEnrichment::query()
            ->where('enrichable_type', $model::class)
            ->where('enrichable_id', $model->getKey())
            ->where('status', 'approved')
            ->latest('approved_at')
            ->latest('id')
            ->get(['kind', 'content', 'model', 'approved_at'])
            ->unique('kind')
            ->map(fn (AiEnrichment $enrichment) => $this->clean([
                'tipo' => $enrichment->kind,
                'contenido' => $this->text($enrichment->content, 1800),
                'modelo' => $enrichment->model,
                'aprobado_en' => $enrichment->approved_at?->toIso8601String(),
            ]))
            ->values()
            ->all();
    }

    /**
     * Separa la consulta en palabras útiles. Se generan variantes con y sin
     * acentos para que "historica" también encuentre "histórica".
     */
    private function searchTerms(string $query): array
    {
        $stopwords = [
            'a', 'al', 'algo', 'algun', 'alguna', 'algunas', 'alguno', 'algunos',
            'autor', 'autores', 'catalogo', 'como', 'con', 'cual', 'cuales',
            'de', 'del', 'donde', 'el', 'ella', 'ellas', 'ellos', 'en', 'es',
            'esa', 'ese', 'eso', 'esta', 'este', 'esto', 'hay', 'la', 'las',
            'libro', 'libros', 'lo', 'los', 'mas', 'me', 'mi', 'mis', 'o',
            'para', 'pero', 'por', 'que', 'se', 'sin', 'sobre', 'su', 'sus',
            'tiene', 'tienen', 'trata', 'tratan', 'un', 'una', 'unas', 'uno',
            'unos', 'y', 'ya',
        ];

        $variants = array_merge(
            $this->tokens($query),
            $this->tokens(Str::ascii($query))
        );

        $terms = [];

        foreach ($variants as $term) {
            if (mb_strlen($term) < 3 || in_array($term, $stopwords, true)) {
                continue;
            }

            $terms[$term] = true;
        }

        if ($terms === [] && trim($query) !== '') {
            $terms[trim($query)] = true;
        }

        return array_keys($terms);
    }

    private function tokens(string $value): array
    {
        $value = Str::lower($value);

        return preg_split('/[^\p{L}\p{N}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    private function text(?string $value, int $limit = self::MAX_TEXT_LENGTH): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(strip_tags($value));

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $limit, '…');
    }

    /**
     * Quita nulos y textos vacíos para que el prompt no se llene de ruido.
     */
    private function clean(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->clean($value);

                if ($value === []) {
                    continue;
                }
            } elseif ($value === null || $value === '') {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
