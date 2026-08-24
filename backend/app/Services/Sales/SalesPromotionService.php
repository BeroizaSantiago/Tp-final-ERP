<?php

namespace App\Services\Sales;

use App\Models\Products\Product;
use App\Models\Products\SalesPromotion;
use App\Models\Sales\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesPromotionService
{
    public function compatible(array $context): Collection
    {
        $date = now()->parse($context['date'] ?? today());
        $products = Product::query()->with(['category','brand'])->whereIn('id', collect($context['items'] ?? [])->pluck('product_id')->filter())->get()->keyBy('id');
        $subtotal = collect($context['items'] ?? [])->sum(fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));
        $quantity = collect($context['items'] ?? [])->sum('quantity');

        return SalesPromotion::query()->with('items')->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('date_from')->orWhereDate('date_from','<=',$date))
            ->where(fn ($q) => $q->whereNull('date_to')->orWhereDate('date_to','>=',$date))
            ->get()->filter(function (SalesPromotion $promotion) use ($context,$date,$products,$subtotal,$quantity) {
                if ($promotion->active_weekdays && !in_array($date->dayOfWeekIso, array_map('intval',$promotion->active_weekdays), true)) return false;
                foreach (['currency_name','company_name','branch_name','price_list_name'] as $field) {
                    if ($field === 'company_name' && empty($context[$field])) continue;
                    if ($promotion->{$field} && strcasecmp($promotion->{$field}, (string) ($context[$field] ?? '')) !== 0) return false;
                }
                if ($promotion->payment_methods && !array_intersect($promotion->payment_methods, $context['payment_methods'] ?? [])) return false;
                if ($subtotal < (float) $promotion->minimum_amount || $quantity < (float) $promotion->minimum_quantity) return false;
                return collect($context['items'] ?? [])->contains(fn ($item) => $this->appliesToProduct($promotion, $products->get($item['product_id'] ?? null)));
            })->map(function (SalesPromotion $promotion) use ($context,$products) {
                $eligibleSubtotal = collect($context['items'] ?? [])->filter(fn ($item) => $this->appliesToProduct($promotion,$products->get($item['product_id'] ?? null)))
                    ->sum(fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));
                $discount = match ($promotion->discount_type) {
                    'percentage' => $eligibleSubtotal * (float) $promotion->discount_value / 100,
                    'fixed_amount','combo' => min($eligibleSubtotal, (float) $promotion->discount_value),
                    'special_price' => collect($context['items'] ?? [])->filter(fn ($item) => $this->appliesToProduct($promotion,$products->get($item['product_id'] ?? null)))
                        ->sum(fn ($item) => max(0, ((float) ($item['unit_price'] ?? 0) - (float) $promotion->discount_value) * (float) ($item['quantity'] ?? 0))),
                    default => 0,
                };
                $promotion->setAttribute('estimated_discount', round($discount, 2));
                return $promotion;
            })->values();
    }

    public function compatibleForInvoice(Invoice $invoice, array $context = []): Collection
    {
        $invoice->loadMissing(['client','items.variant']);
        return $this->compatible($context + [
            'date'=>$invoice->issue_date, 'currency_name'=>$invoice->currency_name,
            'price_list_name'=>$invoice->client?->price_type, 'branch_name'=>$invoice->client?->branch_origin,
            'items'=>$invoice->items->map(fn ($item) => ['product_id'=>$this->productForInvoiceItem($item)?->id,'quantity'=>$item->quantity,'unit_price'=>$item->unit_price_with_taxes])->all(),
        ]);
    }

    public function apply(Invoice $invoice, SalesPromotion $promotion, array $context = []): Invoice
    {
        return DB::transaction(function () use ($invoice,$promotion,$context) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->payments()->where('status', 'approved')->exists()) throw ValidationException::withMessages(['promotion'=>'No se puede cambiar la promoción después de registrar pagos.']);
            if (!$this->compatibleForInvoice($invoice,$context)->contains('id',$promotion->id)) throw ValidationException::withMessages(['promotion'=>'La promoción ya no es compatible con esta venta.']);
            $invoice->load(['items.variant.product']);
            $eligible = $invoice->items->filter(fn ($item) => $this->appliesToProduct($promotion,$this->productForInvoiceItem($item)));
            $eligibleBase = $eligible->sum(fn ($item) => $this->manualBase($item));
            $remainingFixed = min($eligibleBase,(float)$promotion->discount_value);

            foreach ($invoice->items as $item) {
                $base = $this->manualBase($item);
                $discount = 0;
                if ($eligible->contains('id',$item->id)) {
                    $discount = match ($promotion->discount_type) {
                        'percentage' => $base * (float)$promotion->discount_value / 100,
                        'fixed_amount','combo' => $eligibleBase > 0 ? $remainingFixed * $base / $eligibleBase : 0,
                        'special_price' => max(0,$base - ((float)$promotion->discount_value * (float)$item->quantity)),
                        default => 0,
                    };
                }
                $total = max(0,$base-$discount); $rate=(float)$item->tax_aliquot_percentage; $net=$rate>0?$total/(1+$rate/100):$total;
                $item->update(['is_promotion'=>$discount>0,'sales_promotion_id'=>$discount>0?$promotion->id:null,'promotion_discount_amount'=>$discount,
                    'discount_percentage'=>$base>0?(((float)$item->quantity*(float)$item->unit_price_with_taxes-$total)/((float)$item->quantity*(float)$item->unit_price_with_taxes))*100:0,
                    'subtotal_amount'=>$net,'tax_amount'=>$total-$net,'total_amount'=>$total]);
            }
            $items=$invoice->items()->get(); $total=(float)$items->sum('total_amount');
            $invoice->update(['sales_promotion_id'=>$promotion->id,'promotion_discount_amount'=>$items->sum('promotion_discount_amount'),
                'taxed_amount'=>$items->sum('subtotal_amount'),'tax_amount'=>$items->sum('tax_amount'),'total_amount'=>$total,'balance'=>$total]);
            return $invoice->fresh()->load(['salesPromotion','items.variant.size','items.variant.color']);
        });
    }

    private function manualBase($item): float
    {
        $gross=(float)$item->quantity*(float)$item->unit_price_with_taxes;
        return $gross*(1-(float)$item->manual_discount_percentage/100);
    }

    private function appliesToProduct(SalesPromotion $promotion, ?Product $product): bool
    {
        if (!$product) return false;
        if (($promotion->applies_to ?? 'all') === 'all') return true;
        return $promotion->items->contains(fn ($scope) => match ($promotion->applies_to) {
            'product'=>(int)$scope->product_id===(int)$product->id,
            'category'=>(int)$scope->category_id===(int)$product->category_id,
            'brand'=>(int)$scope->brand_id===(int)$product->brand_id,
            default=>false,
        });
    }

    private function productForInvoiceItem($item): ?Product
    {
        if ($item->variant?->product) return $item->variant->product;
        if ($item->product_external_id) {
            $product = Product::where('external_id', $item->product_external_id)->first();
            if ($product) return $product;
        }
        if ($item->product_code) {
            $product = Product::where('code', $item->product_code)->first();
            if ($product) return $product;
        }
        return $item->product_category_id
            ? Product::where('category_id', $item->product_category_id)->where('name', $item->product_name)->first()
            : null;
    }
}
