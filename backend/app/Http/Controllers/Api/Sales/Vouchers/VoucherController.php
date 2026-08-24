<?php

namespace App\Http\Controllers\Api\Sales\Vouchers;

use App\Http\Controllers\Controller;
use App\Models\Sales\Voucher;
use App\Services\Payments\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request, VoucherService $service)
    {
        $service->releaseExpiredReservations();
        return Voucher::query()
            ->with(['usedInvoice:id,full_number', 'reservedInvoice:id,full_number'])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($subquery) use ($request) {
                $search = trim((string) $request->search);
                $subquery->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            }))
            ->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $voucher = Voucher::create($this->validated($request) + [
            'code' => $this->newCode(),
            'status' => 'available',
            'created_by' => auth()->id(),
        ]);
        return response()->json($voucher, 201);
    }

    public function show(Voucher $voucher, VoucherService $service)
    {
        return $service->releaseIfExpired($voucher)->load(['usedInvoice', 'reservedInvoice']);
    }

    public function update(Request $request, Voucher $voucher)
    {
        if ($voucher->status === 'used') return response()->json(['message' => 'Un voucher utilizado no puede editarse.'], 422);
        $voucher->update($this->validated($request));
        return $voucher->fresh();
    }

    public function toggle(Voucher $voucher)
    {
        if ($voucher->status === 'used') return response()->json(['message' => 'Un voucher utilizado no puede habilitarse nuevamente.'], 422);
        if ($voucher->status === 'reserved') return response()->json(['message' => 'El voucher está reservado en una venta.'], 422);
        $voucher->update(['status' => $voucher->status === 'disabled' ? 'available' : 'disabled']);
        return $voucher->fresh();
    }

    public function lookup(string $code, VoucherService $service)
    {
        $value = trim(urldecode($code));
        $voucherId = null;
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($value, PHP_URL_PATH), '/');
            $lastSegment = basename($path);
            $voucherId = ctype_digit($lastSegment) ? (int) $lastSegment : null;
        }

        $voucher = Voucher::query()
            ->when($voucherId, fn ($query) => $query->whereKey($voucherId))
            ->when(! $voucherId, fn ($query) => $query->where('code', $value))
            ->first();
        if (! $voucher) return response()->json(['message' => 'El voucher no existe.'], 404);
        return response()->json($this->present($service->releaseIfExpired($voucher)));
    }

    public function consume(Voucher $voucher, VoucherService $service)
    {
        return DB::transaction(fn () => response()->json([
            'message' => 'Voucher utilizado correctamente.',
            'voucher' => $this->present($service->consume($voucher)),
        ]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'value_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'amount' => ['required', 'numeric', 'min:0.01', Rule::when($request->input('value_type') === 'percentage', ['max:100'])],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0.01'],
            'message' => ['nullable', 'string', 'max:2000'],
            'delivery_type' => ['required', Rule::in(['physical', 'digital'])],
            'sales_channel' => ['required', Rule::in(['store', 'online'])],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['value_type'] ??= 'fixed';
        if ($data['value_type'] === 'fixed') $data['maximum_discount_amount'] = null;

        return $data;
    }

    private function newCode(): string
    {
        do { $code = 'VCH-'.now()->format('Y').'-'.Str::upper(Str::random(12)); }
        while (Voucher::query()->where('code', $code)->exists());
        return $code;
    }

    private function present(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id, 'code' => $voucher->code, 'name' => $voucher->name,
            'amount' => $voucher->amount, 'value_type' => $voucher->value_type,
            'maximum_discount_amount' => $voucher->maximum_discount_amount, 'message' => $voucher->message,
            'status' => $voucher->isExpired() && $voucher->status === 'available' ? 'expired' : $voucher->status,
            'expires_at' => $voucher->expires_at?->toDateString(), 'used_at' => $voucher->used_at?->toIso8601String(),
        ];
    }
}
