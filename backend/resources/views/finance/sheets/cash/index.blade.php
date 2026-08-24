{{-- Vista: Listado de Planillas de Caja. Muestra la consulta principal y las acciones disponibles de Planillas de Caja. --}}
@include('finance.sheets.shared.sheets-index', [
    'title' => 'Planillas de Caja',
    'subtitle' => 'Aperturas, cierres y control diario de caja',
    'apiUrl' => url('/api/cash-sheets'),
    'showBaseUrl' => url('/demo/finance/cash-sheets'),
    'createUrl' => url('/demo/finance/cash-sheets/create'),
])
