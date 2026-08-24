<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithFormatData;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ProductCatalogImport implements ToCollection, WithFormatData, WithLimit, WithStartRow
{
    public array $headers = [];

    public array $headerLabels = [];

    public Collection $rows;

    private int $nextSourceRow;

    public function __construct(
        array $headers = [],
        array $headerLabels = [],
        private readonly int $firstRow = 1,
        private readonly int $rowLimit = 2000,
    )
    {
        $this->headers = $headers;
        $this->headerLabels = $headerLabels;
        $this->rows = collect();
        $this->nextSourceRow = $firstRow;
    }

    public function collection(Collection $rows): void
    {
        if ($this->headers === []) {
            $headerRow = collect($rows->shift() ?? []);
            $this->headers = $headerRow
                ->map(function ($header, $index) {
                    $normalized = $this->normalizeHeader((string) $header, (int) $index);
                    $this->headerLabels[$normalized] = trim((string) $header);
                    return $normalized;
                })->all();
        }

        $mappedRows = $rows
            ->map(function ($row) {
                $values = collect($row)->values()->all();
                $record = [];

                foreach ($this->headers as $index => $header) {
                    $record[$header] = $values[$index] ?? null;
                }

                $record['_source_row'] = $this->nextSourceRow++;

                return $record;
            })
            ->filter(fn (array $row) => collect($row)->contains(fn ($value) => trim((string) $value) !== ''))
            ->values();

        $this->rows = $this->rows->concat($mappedRows)->values();
    }

    public function startRow(): int
    {
        return $this->firstRow;
    }

    public function limit(): int
    {
        return $this->rowLimit;
    }

    private function normalizeHeader(string $header, int $index): string
    {
        $header = mb_strtolower(Str::ascii(trim($header)));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim((string) $header, '_');

        return $header !== '' ? $header : "column_{$index}";
    }
}
