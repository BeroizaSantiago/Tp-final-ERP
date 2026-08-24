<?php

namespace App\Services\Reports\CompanyPosition;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Comparte períodos, sucursales y gráficos entre los reportes de posición. */
class CompanyPositionReportSupport
{
    public function periods(string $from, string $to): Collection
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $days = $start->diffInDays($end) + 1;
        // Evita colocar decenas de fechas diarias en el eje horizontal.
        $unit = $days <= 14 ? 'day' : ($days <= 90 ? 'week' : ($days <= 730 ? 'month' : 'year'));

        if ($unit === 'day') {
            return collect(CarbonPeriod::create($start, $end))->mapWithKeys(fn (Carbon $date) => [
                $date->format('Y-m-d') => ['key' => $date->format('Y-m-d'), 'label' => $date->format('d/m/Y')],
            ]);
        }

        $cursor = match ($unit) {
            'week' => $start->copy()->startOfWeek(),
            'month' => $start->copy()->startOfMonth(),
            default => $start->copy()->startOfYear(),
        };
        $periods = collect();
        while ($cursor->lte($end)) {
            $key = $this->periodKey($cursor, $unit);
            $label = match ($unit) {
                'week' => $cursor->format('d/m').' - '.$cursor->copy()->endOfWeek()->format('d/m/Y'),
                'month' => ucfirst($cursor->locale('es')->translatedFormat('M Y')),
                default => $cursor->format('Y'),
            };
            $periods->put($key, compact('key', 'label'));
            $cursor->add(1, $unit);
        }
        return $periods;
    }

    public function key($date, Collection $periods): string
    {
        $sample = (string) $periods->keys()->first();
        $unit = str_contains($sample, '-W') ? 'week' : (strlen($sample) === 7 ? 'month' : (strlen($sample) === 4 ? 'year' : 'day'));
        return $this->periodKey(Carbon::parse($date), $unit);
    }

    public function branches(): Collection
    {
        $queries = [
            DB::table('cash_sheets')->select('branch_name')->whereNotNull('branch_name')->where('branch_name', '!=', ''),
            DB::table('misc_expenses')->select('branch_name')->whereNotNull('branch_name')->where('branch_name', '!=', ''),
            DB::table('bank_movements')->select('branch_name')->whereNotNull('branch_name')->where('branch_name', '!=', ''),
        ];
        $union = array_shift($queries);
        foreach ($queries as $query) $union->union($query);
        return DB::query()->fromSub($union, 'branches')->distinct()->orderBy('branch_name')->pluck('branch_name');
    }

    public function chart(Collection $rows, string $firstKey, string $secondKey, string $firstLabel, string $secondLabel): string
    {
        $width = 1040; $height = 310; $left = 65; $top = 32; $bottom = 55; $plotHeight = $height - $top - $bottom;
        $values = $rows->flatMap(fn ($row) => [(float) $row[$firstKey], (float) $row[$secondKey]]);
        $dataMax = max(1, (float) $values->max());
        $step = $this->niceStep($dataMax / 4);
        $ticks = max(4, (int) ceil($dataMax / $step));
        if ($ticks > 6) { $step = $this->niceStep($step * 1.6); $ticks = (int) ceil($dataMax / $step); }
        $max = $step * $ticks;
        $count = max(1, $rows->count()); $groupWidth = ($width - $left - 20) / $count; $barWidth = min(24, $groupWidth * .28);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'">'.
            '<rect width="100%" height="100%" fill="#fff"/><style>text{font-family:DejaVu Sans,Arial;fill:#5d596c;font-size:10px}.legend{font-size:12px}</style>';
        for ($i = 0; $i <= $ticks; $i++) {
            $y = $top + ($plotHeight * $i / $ticks); $value = $max - ($step * $i);
            $svg .= '<line x1="'.$left.'" y1="'.$y.'" x2="'.($width - 15).'" y2="'.$y.'" stroke="#e7e7ef"/>'.
                '<text x="'.($left - 7).'" y="'.($y + 3).'" text-anchor="end">'.$this->shortNumber($value).'</text>';
        }
        foreach ($rows->values() as $index => $row) {
            $center = $left + $groupWidth * ($index + .5);
            foreach ([[$firstKey, '#8c57ff', -$barWidth - 2], [$secondKey, '#56ca00', 2]] as [$key, $color, $offset]) {
                $barHeight = max(0, (float) $row[$key]) / $max * $plotHeight;
                $svg .= '<rect x="'.($center + $offset).'" y="'.($top + $plotHeight - $barHeight).'" width="'.$barWidth.'" height="'.$barHeight.'" rx="2" fill="'.$color.'"/>';
            }
            $periodLabel = (string) $row['period'];
            if (str_contains($periodLabel, ' - ')) $periodLabel = 'Sem. '.explode(' - ', $periodLabel)[0];
            $label = htmlspecialchars(mb_strimwidth($periodLabel, 0, 12, '...'), ENT_QUOTES, 'UTF-8');
            $svg .= '<text x="'.$center.'" y="'.($height - 30).'" text-anchor="middle">'.$label.'</text>';
        }
        $svg .= '<rect x="'.($width - 410).'" y="8" width="11" height="11" rx="2" fill="#8c57ff"/><text class="legend" x="'.($width - 393).'" y="18">'.htmlspecialchars($firstLabel).'</text>'.
            '<rect x="'.($width - 210).'" y="8" width="11" height="11" rx="2" fill="#56ca00"/><text class="legend" x="'.($width - 193).'" y="18">'.htmlspecialchars($secondLabel).'</text></svg>';
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function periodKey(Carbon $date, string $unit): string
    {
        return match ($unit) {
            'week' => $date->format('o-\WW'), 'month' => $date->format('Y-m'), 'year' => $date->format('Y'), default => $date->format('Y-m-d'),
        };
    }

    private function shortNumber(float $value): string
    {
        if ($value >= 1000000) return rtrim(rtrim(number_format($value / 1000000, 1, ',', ''), '0'), ',').' Mill.';
        if ($value >= 1000) return number_format($value / 1000, 0, ',', '.').' mil';
        return number_format($value, 0, ',', '.');
    }

    /** Devuelve pasos legibles: 100 mil, 200 mil, 250 mil, 500 mil, etc. */
    private function niceStep(float $raw): float
    {
        if ($raw <= 0) return 1;
        $magnitude = 10 ** floor(log10($raw));
        $fraction = $raw / $magnitude;
        $nice = match (true) {
            $fraction <= 1.5 => 1,
            $fraction <= 2.25 => 2,
            $fraction <= 3.75 => 2.5,
            $fraction <= 7.5 => 5,
            default => 10,
        };
        return $nice * $magnitude;
    }
}
