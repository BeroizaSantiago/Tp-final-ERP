{{-- Vista: Detalle de Planillas de Caja. Muestra la información completa de un registro de Planillas de Caja. --}}
@include('finance.sheets.shared.sheet-show', [
    'backUrl' => url('/demo/finance/cash-sheets'),
    'apiUrl' => url('/api/cash-sheets/' . $cashSheetId),
    'title' => 'Planilla de Caja',
])
