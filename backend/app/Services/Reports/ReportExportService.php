<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/** Servicio reutilizable para generar documentos de los módulos de Reportes. */
class ReportExportService
{
    public function pdf(string $view, array $data, string $filename, string $orientation = 'landscape', string $paper = 'A4'): Response
    {
        $runtimePath = storage_path('framework/cache/dompdf');
        File::ensureDirectoryExists($runtimePath);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', $runtimePath);
        $options->set('fontCache', $runtimePath);
        // En Windows/Apache tempnam puede devolver false y FontLib intenta abrir
        // una ruta vacía al crear subconjuntos. La fuente interna completa evita
        // ese archivo temporal y conserva correctamente tildes y eñes.
        $options->set('isFontSubsettingEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->setPaper($paper, $orientation);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function spreadsheet(object $export, string $filename, string $format)
    {
        return Excel::download($export, $filename, $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX);
    }
}
