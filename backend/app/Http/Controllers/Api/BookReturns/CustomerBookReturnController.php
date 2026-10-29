<?php

namespace App\Http\Controllers\Api\BookReturns;

use App\Http\Controllers\Controller;
use App\Services\BookReturns\BookReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerBookReturnController extends Controller
{
    

public function index(Request $request): JsonResponse
{
    $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'status' => ['nullable', 'in:pending,approved,rejected,completed'],
        'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
        'per_page' => ['nullable', 'integer', 'between:5,100'],
    ]);

    $query = DB::table('book_return_sales as r')
        ->leftJoin('clients as c', 'c.id', '=', 'r.client_id')
        ->leftJoin('invoices as inv', 'inv.id', '=', 'r.invoice_id')
        ->select([
            'r.id',
            'r.invoice_id',
            'r.client_id',
            'c.name as client_name',
            'inv.full_number',
            'inv.receipt_type_name',
            'r.return_date',
            'r.reason',
            'r.status',
            'r.stock_applied_at',
            'r.created_at',
        ]);

    // Buscar por factura, cliente o motivo.
    if ($request->filled('search')) {
        $search = trim($request->input('search'));

        $query->where(function ($q) use ($search) {
            $q->where('inv.full_number', 'like', "%{$search}%")
                ->orWhere('c.name', 'like', "%{$search}%")
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

    // Filtrar por ID de factura, si se proporciona.
    if ($request->filled('invoice_id')) {
        $query->where(
            'r.invoice_id',
            $request->integer('invoice_id')
        );
    }

    $paginator = $query
        ->orderByDesc('r.id')
        ->paginate($request->integer('per_page', 20))
        ->withQueryString();

    $returnIds = collect($paginator->items())
        ->pluck('id')
        ->all();

    $returnItems = DB::table('book_return_sale_items as i')
        ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
        ->whereIn('i.book_return_sale_id', $returnIds)
        ->select([
            'i.id',
            'i.book_return_sale_id',
            'i.invoice_item_id',
            'i.product_id',
            'i.product_variant_id',
            'i.quantity',
            'i.condition',
            'i.notes',
            'p.name as product_name',
        ])
        ->orderBy('i.id')
        ->get()
        ->groupBy('book_return_sale_id');

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



    public function show(int $customerBookReturn): JsonResponse
    {
        $return = DB::table('book_return_sales as r')
            ->leftJoin('clients as c', 'c.id', '=', 'r.client_id')
            ->leftJoin('invoices as inv', 'inv.id', '=', 'r.invoice_id')
            ->where('r.id', $customerBookReturn)
            ->select([
                'r.*',
                'c.name as client_name',
                'inv.full_number',
            ])
            ->first();

        if (! $return) {
            return response()->json([
                'message' => 'No se encontró la devolución del cliente.',
            ], 404);
        }

        $return->items = DB::table('book_return_sale_items as i')
            ->leftJoin('invoice_items as source', 'source.id', '=', 'i.invoice_item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->where('i.book_return_sale_id', $return->id)
            ->select([
                'i.id',
                'i.invoice_item_id',
                'i.product_id',
                'i.product_variant_id',
                'i.quantity',
                'i.condition',
                'i.notes',
                'source.product_name as source_product_name',
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
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:invoice_items,id',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.condition' => [
                'sometimes',
                'in:sellable,damaged,review',
            ],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $result = $service->createCustomerReturn($data);

        return response()->json([
            'message' => $result['duplicate']
                ? 'La devolución ya había sido registrada.'
                : 'Devolución del cliente registrada con estado pendiente.',
            'data' => $result,
        ], $result['duplicate'] ? 200 : 201);
    }

    public function updateStatus(
        Request $request,
        int $customerBookReturn,
        BookReturnService $service
    ): JsonResponse {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
        ]);

        $result = $service->updateStatus(
            'customer',
            $customerBookReturn,
            $data['status']
        );

        return response()->json([
            'message' => 'Estado de la devolución actualizado.',
            'data' => $result,
        ]);
    }
}
