<?php

namespace App\Http\Controllers\Api\BookReturns;

use App\Http\Controllers\Controller;
use App\Services\BookReturns\BookReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierBookReturnController extends Controller
{
    
    
public function index(Request $request): JsonResponse
{
    $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'status' => ['nullable', 'in:pending,approved,rejected,completed'],
        'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
        'receipt_type_name' => ['nullable', 'string', 'max:100'],
        'per_page' => ['nullable', 'integer', 'between:5,100'],
    ]);

    $query = DB::table('book_return_purchases as r')
        ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
        ->leftJoin('purchases as pu', 'pu.id', '=', 'r.purchase_id')
        ->select([
            'r.id',
            'r.purchase_id',
            'r.provider_id',
            'p.name as provider_name',
            'pu.receipt_type_name',
            'pu.full_number',
            'pu.issue_date',
            'r.return_date',
            'r.reason',
            'r.status',
            'r.stock_applied_at',
            'r.created_at',
        ]);

    // Buscar por comprobante, proveedor o motivo.
    if ($request->filled('search')) {
        $search = trim($request->input('search'));

        $query->where(function ($q) use ($search) {
            $q->where('pu.full_number', 'like', "%{$search}%")
                ->orWhere('p.name', 'like', "%{$search}%")
                ->orWhere('r.reason', 'like', "%{$search}%");
        });
    }

    // Filtrar por estado.
    if ($request->filled('status')) {
        $query->where(
            'r.status',
            $request->string('status')->toString()
        );
    }

    // Filtrar por ID de compra, si se proporciona.
    if ($request->filled('purchase_id')) {
        $query->where(
            'r.purchase_id',
            $request->integer('purchase_id')
        );
    }

    // Filtrar por tipo comprobante.
    if ($request->filled('receipt_type_name')) {
    $query->where(
        'pu.receipt_type_name',
        $request->input('receipt_type_name')
    );
}

    $paginator = $query
        ->orderByDesc('r.id')
        ->paginate($request->integer('per_page', 20))
        ->withQueryString();

    // Obtener los libros devueltos de las devoluciones de esta página.
    $returnIds = collect($paginator->items())
        ->pluck('id')
        ->all();

    $returnItems = DB::table('book_return_purchase_items as i')
        ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
        ->whereIn('i.book_return_purchase_id', $returnIds)
        ->select([
            'i.id',
            'i.book_return_purchase_id',
            'i.purchase_item_id',
            'i.product_id',
            'i.product_variant_id',
            'i.quantity',
            'i.condition',
            'i.notes',
            'p.name as product_name',
        ])
        ->orderBy('i.id')
        ->get()
        ->groupBy('book_return_purchase_id');

    $paginator->getCollection()->transform(
        function ($return) use ($returnItems) {
            $return->items = $returnItems
                ->get($return->id, collect())
                ->values();

            return $return;
        }
    );

    return response()->json($paginator);
}



    public function show(int $supplierBookReturn): JsonResponse
    {
        $return = DB::table('book_return_purchases as r')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('purchases as pu', 'pu.id', '=', 'r.purchase_id')
            ->where('r.id', $supplierBookReturn)
            ->select([
                'r.*',
                'p.name as provider_name',
                'pu.receipt_type_name',
                'pu.full_number',
            ])
            ->first();

        if (! $return) {
            return response()->json([
                'message' => 'No se encontró la devolución al proveedor.',
            ], 404);
        }

        $return->items = DB::table('book_return_purchase_items as i')
            ->leftJoin('purchase_items as source', 'source.id', '=', 'i.purchase_item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->where('i.book_return_purchase_id', $return->id)
            ->select([
                'i.id',
                'i.purchase_item_id',
                'i.product_id',
                'i.product_variant_id',
                'i.quantity',
                'i.condition',
                'i.notes',
                'source.product_name as source_description',
                'p.name as product_name',
            ])
            ->orderBy('i.id')
            ->get();

        return response()->json(['data' => $return]);
    }

    public function store(
        Request $request,
        BookReturnService $service
    ): JsonResponse {
        $data = $request->validate([
            'purchase_id' => [
                'required',
                'integer',
                'exists:purchases,id',
            ],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:purchase_items,id',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.condition' => [
                'sometimes',
                'in:sellable,damaged,review',
            ],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $result = $service->createSupplierReturn($data);

        return response()->json([
            'message' => $result['duplicate']
                ? 'La devolución ya había sido registrada.'
                : 'Devolución al proveedor registrada con estado pendiente.',
            'data' => $result,
        ], $result['duplicate'] ? 200 : 201);
    }

    public function updateStatus(
        Request $request,
        int $supplierBookReturn,
        BookReturnService $service
    ): JsonResponse {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
        ]);

        $result = $service->updateStatus(
            'supplier',
            $supplierBookReturn,
            $data['status']
        );

        return response()->json([
            'message' => 'Estado de la devolución actualizado.',
            'data' => $result,
        ]);
    }
}
