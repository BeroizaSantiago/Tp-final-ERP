<?php

return [
    // Anchos habituales de comandera: 80 mm o 58 mm.
    'thermal_paper_width_mm' => (int) env('THERMAL_PAPER_WIDTH_MM', 80),
    // Vacío utiliza la impresora predeterminada del equipo donde corre QZ Tray.
    'thermal_printer_name' => env('THERMAL_PRINTER_NAME'),
];
