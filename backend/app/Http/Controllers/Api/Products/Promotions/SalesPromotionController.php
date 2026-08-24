<?php

namespace App\Http\Controllers\Api\Products\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Products\SalesPromotion;
use App\Models\Products\SalesPromotionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sales\Invoice;
use App\Services\Sales\SalesPromotionService;

/**
 * Gestiona promociones de venta y sus alcances.
 *
 * Registra promociones por fecha, moneda, tipo de descuento y alcance
 * general, por producto, categoria o marca, guardando sus items asociados
 * dentro de una transaccion.
 */
class SalesPromotionController extends Controller
{
    public function __construct(private readonly SalesPromotionService $promotionService) {}

    public function index(Request $request)
    {
        return SalesPromotion::with('items')
            ->when($request->filled('search'), fn ($q) => $q->where('name','like','%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active',$request->status === 'active'))
            ->when($request->filled('validity'), function ($q) use ($request) {
                if ($request->validity === 'current') $q->where(fn($x)=>$x->whereNull('date_from')->orWhereDate('date_from','<=',today()))->where(fn($x)=>$x->whereNull('date_to')->orWhereDate('date_to','>=',today()));
                if ($request->validity === 'expired') $q->whereDate('date_to','<',today());
                if ($request->validity === 'future') $q->whereDate('date_from','>',today());
            })
            ->latest()
            ->paginate(20);
    }

    public function show(SalesPromotion $salesPromotion)
    {
        return $salesPromotion->load('items.product', 'items.category', 'items.brand');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return DB::transaction(fn () => $this->persist(new SalesPromotion(), $data));
    }

    public function update(Request $request, SalesPromotion $salesPromotion)
    {
        $data = $this->validated($request);
        return DB::transaction(fn () => $this->persist($salesPromotion, $data));
    }

    public function toggle(SalesPromotion $salesPromotion)
    {
        $salesPromotion->update(['is_active'=>!$salesPromotion->is_active]);
        return $salesPromotion->fresh();
    }

    public function compatible(Request $request)
    {
        $data=$request->validate(['date'=>['nullable','date'],'currency_name'=>['nullable','string'],'company_name'=>['nullable','string'],'branch_name'=>['nullable','string'],'price_list_name'=>['nullable','string'],'payment_methods'=>['nullable','array'],'items'=>['required','array','min:1'],'items.*.product_id'=>['required','exists:products,id'],'items.*.quantity'=>['required','numeric','min:0.01'],'items.*.unit_price'=>['required','numeric','min:0']]);
        return $this->promotionService->compatible($data);
    }

    public function compatibleInvoice(Request $request, Invoice $invoice)
    {
        return $this->promotionService->compatibleForInvoice($invoice,$request->only(['payment_methods','company_name','branch_name','price_list_name']));
    }

    public function apply(Request $request, Invoice $invoice, SalesPromotion $salesPromotion)
    {
        return $this->promotionService->apply($invoice,$salesPromotion,$request->only(['payment_methods','company_name','branch_name','price_list_name']));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string'],
            'promotion_type'=>['required','string','max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date','after_or_equal:date_from'],
            'active_weekdays'=>['nullable','array'],'active_weekdays.*'=>['integer','between:1,7'],
            'currency_name' => ['nullable', 'string'],
            'company_name'=>['nullable','string'],'branch_name'=>['nullable','string'],'price_list_name'=>['nullable','string'],
            'payment_methods'=>['nullable','array'],'payment_methods.*'=>['string'],
            'applies_to' => ['required', 'in:all,product,category,brand'],
            'discount_type' => ['required', 'in:percentage,fixed_amount,special_price,combo'],
            'discount_value' => ['required', 'numeric','min:0'],
            'display_mode'=>['required','in:line,global'],'minimum_amount'=>['nullable','numeric','min:0'],'minimum_quantity'=>['nullable','numeric','min:0'],
            'is_active' => ['nullable', 'boolean'],

            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.category_id' => ['nullable', 'exists:product_categories,id'],
            'items.*.brand_id' => ['nullable', 'exists:brands,id'],
        ]);
    }

    private function persist(SalesPromotion $promotion, array $data): SalesPromotion
    {
        $promotion->fill(collect($data)->except('items')->all())->save();
        $promotion->items()->delete();
        foreach ($data['items'] ?? [] as $item) $promotion->items()->create($item);
        return $promotion->load('items.product','items.category','items.brand');
    }
}
