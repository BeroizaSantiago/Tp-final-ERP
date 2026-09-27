<?php

namespace App\Http\Controllers\Api\Products\Masters\Concerns;

/**
 * Valores por defecto de los catalogos maestros.
 *
 * Las tablas de maestros declaran columnas NOT NULL con default (por ejemplo
 * web_order, que es NOT NULL DEFAULT 0) y el middleware ConvertEmptyStringsToNull
 * de Laravel convierte los campos vacios en null antes de validar. Si ese null
 * llega al insert, MySQL corta la consulta con un error de integridad en lugar de
 * usar el default de la columna. Se completan aca para que el cliente pueda dejar
 * el campo vacio sin romper el guardado.
 */
trait AppliesCatalogDefaults
{
    /** Defaults de las columnas NOT NULL de los catálogos maestros. */
    private array $catalogDefaults = [
        'web_order' => 0,
        'is_active' => true,
    ];

    /**
     * Reemplaza por su default los campos presentes pero nulos.
     */
    private function withCatalogDefaults(array $data): array
    {
        foreach ($this->catalogDefaults as $key => $default) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = $default;
            }
        }

        return $data;
    }
}
