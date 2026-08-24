<?php

namespace App\Services\Reports\Sales;

use App\Models\Clients\Client;
use App\Models\Finance\CashSheet;
use App\Models\Products\Brand;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use App\Models\Purchases\Provider;
use App\Models\Purchases\PurchaseItem;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Construye el reporte de ventas a nivel de ítem y todos sus totalizadores. */
class DetailedSalesReportService
{
    public function generate(array $filters): array
    {
        $documents=$this->documents($filters)->get(); $items=$documents->flatMap->items;
        $products=$this->productContext($items); $history=$this->costHistory($items);
        $providersByName=Provider::query()->get()->keyBy(fn(Provider $p)=>mb_strtolower((string)$p->name));
        $users=User::query()->get()->keyBy('id'); $identities=$this->userIdentities($users);

        $rows=$documents->flatMap(function(Invoice $invoice)use($filters,$products,$history,$users,$identities,$providersByName){
            $factor=$this->isCredit($invoice)?-1:1; $seller=$this->seller($invoice,$users,$identities);
            return $invoice->items->map(function(InvoiceItem $item)use($invoice,$filters,$products,$history,$factor,$seller,$providersByName){
                $product=$item->variant?->product
                    ??$products['external']->get((string)$item->product_external_id)
                    ??$products['name']->get(mb_strtolower((string)$item->product_name));
                $purchase=$this->historicalPurchase($item,$product,$invoice,$history);
                $provider=$purchase?->purchase?->provider
                    ??($product?->principal_provider_name?$providersByName->get(mb_strtolower((string)$product->principal_provider_name)):null);
                $quantity=(float)$item->quantity; $unitCost=(float)($purchase?->unit_price??$this->currentCost($product));
                $cost=$unitCost*$quantity; $subtotal=(float)$item->subtotal_amount;
                $itemTotal=(float)$item->total_amount ?: $quantity*(float)($item->unit_price_with_taxes?:$item->unit_price);
                $discount=(float)$item->discount_amount+(float)$item->promotion_discount_amount;
                if($discount==0){$gross=$quantity*(float)($item->unit_price_with_taxes?:$item->unit_price);$discount=max(0,$gross-$itemTotal);}
                $iva=(float)$item->tax_amount; $profit=$subtotal-$cost;
                $variant=$filters['with_variant']?$this->variantLabel($item):'';
                return [
                    'invoice_id'=>$invoice->id,'client_id'=>$invoice->client_id,'product_id'=>$product?->id,'variant_id'=>$item->product_variant_id,'provider_id'=>$provider?->id,
                    'brand_id'=>$product?->brand_id,'model_id'=>$product?->product_model_id,'category_id'=>$item->product_category_id?:$product?->category_id,
                    'seller_id'=>$seller?->id,'receipt_type'=>$this->typeLabel($invoice),'date'=>optional($invoice->issue_date)->format('d/m/Y'),
                    'number'=>$invoice->full_number?:'Comprobante #'.$invoice->id,'channel'=>$this->channel($invoice),
                    'branch'=>$invoice->report_branch_name?:'Sin sucursal','point_of_sale'=>$this->pointOfSale($invoice),
                    'client'=>$invoice->client?->name?:$invoice->customer_name?:'Consumidor final','product'=>$item->product_name?:$product?->name?:$item->description?:'Sin producto',
                    'provider'=>$provider?->name?:$product?->principal_provider_name?:'Sin proveedor',
                    'brand'=>$item->brand_name?:$product?->getRelation('brand')?->name?:$product?->getAttribute('brand')?:'Sin marca',
                    'model'=>$item->model_name?:$product?->getRelation('model')?->name?:$product?->getAttribute('model')?:'Sin modelo',
                    'category'=>$item->product_category_name?:$product?->getRelation('category')?->name?:$product?->getAttribute('category')?:'Sin categoría',
                    'price_type'=>$invoice->client?->price_type?:'Sin informar','internal_code'=>$item->product_code?:$product?->code?:'—',
                    'barcode'=>$item->variant_barcode?:$item->product_barcode?:$item->variant?->bar_code?:$product?->bar_code?:'—',
                    'reference_code'=>$item->product_reference_code?:$product?->reference_code?:'—','size'=>$item->size_name?:$item->variant?->size?->name?:'—',
                    'color'=>$item->color_name?:$item->variant?->color?->name?:'—','variant'=>$variant,
                    'quantity'=>round($factor*$quantity,4),'cost'=>round($factor*$cost,2),
                    'unit_price'=>round((float)($item->unit_price_with_taxes?:$item->unit_price),2),
                    'discount'=>round($factor*$discount,2),'discount_percentage'=>(float)($item->manual_discount_percentage?:$item->discount_percentage),
                    'tax_rate'=>(float)$item->tax_aliquot_percentage,'tax_amount'=>round($factor*$iva,2),
                    'profit'=>round($factor*$profit,2),'subtotal'=>round($factor*$subtotal,2),'total'=>round($factor*$itemTotal,2),
                    'payment_method'=>$this->paymentMethods($invoice),'financing'=>$this->financing($invoice),
                ];
            });
        })->when($filters['branch']??null,fn(Collection $r,$v)=>$r->where('branch',$v))
          ->when($filters['point_of_sale']??null,fn(Collection $r,$v)=>$r->where('point_of_sale',$v))
          ->when($filters['channel']??null,fn(Collection $r,$v)=>$r->where('channel',$v))
          ->when($filters['product_id']??null,fn(Collection $r,$v)=>$r->where('product_id',(int)$v))
          ->when($filters['provider_id']??null,fn(Collection $r,$v)=>$r->where('provider_id',(int)$v))
          ->when($filters['brand_id']??null,fn(Collection $r,$v)=>$r->where('brand_id',(int)$v))
          ->when($filters['model_id']??null,fn(Collection $r,$v)=>$r->where('model_id',(int)$v))
          ->when($filters['category_id']??null,fn(Collection $r,$v)=>$r->where('category_id',(int)$v))
          ->when($filters['seller_id']??null,fn(Collection $r,$v)=>$r->where('seller_id',(int)$v))
          ->when($filters['price_type']??null,fn(Collection $r,$v)=>$r->where('price_type',$v))->values();

        return ['filters'=>$filters,'rows'=>$rows->all(),'summary'=>[
            'quantity'=>round((float)$rows->sum('quantity'),4),'cost'=>round((float)$rows->sum('cost'),2),
            'sale_amount'=>round((float)$rows->sum('subtotal'),2),'discount'=>round((float)$rows->sum('discount'),2),
            'iva'=>round((float)$rows->sum('tax_amount'),2),'profit'=>round((float)$rows->sum('profit'),2),
            'total'=>round((float)$rows->sum('total'),2),'records'=>$rows->count(),
        ]];
    }

    public function exportRows(array $report):Collection
    {
        return collect($report['rows'])->map(function(array $r)use($report){
            $row=[$r['receipt_type'],$r['date'],$r['number'],$r['channel'],$r['branch'],$r['client'],$r['product'],$r['brand'],$r['model'],$r['category'],$r['price_type'],$r['internal_code'],$r['barcode'],$r['reference_code']];
            if($report['filters']['with_variant'])array_push($row,$r['size'],$r['color'],$r['variant']);
            return array_merge($row,[$r['quantity'],$r['cost'],$r['unit_price'],$r['discount'],$r['discount_percentage'],$r['tax_rate'],$r['tax_amount'],$r['profit'],$r['total'],$r['payment_method'],$r['financing']]);
        });
    }

    public function options():array
    {
        return ['branches'=>CashSheet::query()->whereNotNull('branch_name')->where('branch_name','!=','')->distinct()->orderBy('branch_name')->pluck('branch_name'),
            'points_of_sale'=>$this->pointsOfSale(),'clients'=>Client::query()->orderBy('name')->get(['id','name','document_number']),
            'channels'=>Invoice::query()->get(['service_channel','service_channel_id','ecommerce_number'])->map(fn($i)=>$this->channel($i))->unique()->sort()->values(),
            'products'=>collect(),
            'providers'=>Provider::query()->where('is_active',true)->orderBy('name')->get(['id','name']),
            'brands'=>Brand::query()->orderBy('name')->get(['id','name']),'models'=>ProductModel::query()->orderBy('name')->get(['id','name']),
            'categories'=>ProductCategory::query()->orderBy('name')->get(['id','name']),
            'sellers'=>User::query()->where('is_active',true)->orderBy('name')->get(['id','name','username']),
            'price_types'=>Client::query()->whereNotNull('price_type')->where('price_type','!=','')->distinct()->orderBy('price_type')->pluck('price_type')];
    }

    private function documents(array $filters):Builder
    {
        $branch=DB::table('cash_sheet_movements as detail_movements')->join('cash_sheets as detail_sheets','detail_sheets.id','=','detail_movements.cash_sheet_id')->select('detail_sheets.branch_name')->whereColumn('detail_movements.invoice_id','invoices.id')->whereNotNull('detail_sheets.branch_name')->orderBy('detail_movements.id')->limit(1);
        $seller=DB::table('cash_sheet_movements as detail_seller_movements')->select('detail_seller_movements.user_id')->whereColumn('detail_seller_movements.invoice_id','invoices.id')->whereNotNull('detail_seller_movements.user_id')->orderBy('detail_seller_movements.id')->limit(1);
        $pos=DB::table('cash_sheet_movements as detail_pos_movements')->join('cash_sheets as detail_pos_sheets','detail_pos_sheets.id','=','detail_pos_movements.cash_sheet_id')->select('detail_pos_sheets.pos_name')->whereColumn('detail_pos_movements.invoice_id','invoices.id')->whereNotNull('detail_pos_sheets.pos_name')->orderBy('detail_pos_movements.id')->limit(1);
        return Invoice::query()->select('invoices.*')->selectSub($branch,'report_branch_name')->selectSub($seller,'report_seller_user_id')->selectSub($pos,'report_point_of_sale')
            ->with(['client:id,name,price_type','items.variant.product.category','items.variant.product.brand','items.variant.product.model','items.variant.size','items.variant.color','payments:id,invoice_id,payment_method,card_plan'])
            ->whereDate('issue_date','>=',$filters['date_from'])->whereDate('issue_date','<=',$filters['date_to'])
            ->where(function(Builder $q){$q->whereIn('receipt_types_prefix',['FV','NC','ND'])->orWhere(function(Builder $legacy){$legacy->whereNull('receipt_types_prefix')->where(function(Builder $types){$types->where('receipt_type_name','like','%Factura%')->orWhere('receipt_type_name','like','%Nota de Crédito%')->orWhere('receipt_type_name','like','%Nota de Credito%')->orWhere('receipt_type_name','like','%Nota de Débito%')->orWhere('receipt_type_name','like','%Nota de Debito%');});});})
            ->whereRaw("LOWER(COALESCE(receipt_type_name,'')) NOT LIKE '%compra%'")->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['client_id']??null,fn(Builder $q,$id)=>$q->where('client_id',$id))->orderBy('issue_date')->orderBy('id');
    }

    private function productContext(Collection $items):array
    {
        $external=$items->pluck('product_external_id')->filter()->unique();$names=$items->pluck('product_name')->filter()->unique();
        if($external->isEmpty()&&$names->isEmpty())$products=collect();else $products=Product::query()->with(['category','brand','model'])->where(function(Builder $q)use($external,$names){if($external->isNotEmpty())$q->whereIn('external_id',$external);if($names->isNotEmpty())($external->isNotEmpty()?$q->orWhereIn('name',$names):$q->whereIn('name',$names));})->get();
        return ['external'=>$products->filter(fn($p)=>$p->external_id!==null)->keyBy(fn($p)=>(string)$p->external_id),'name'=>$products->keyBy(fn($p)=>mb_strtolower((string)$p->name))];
    }

    private function costHistory(Collection $items):Collection
    {
        $variants=$items->pluck('product_variant_id')->filter()->unique();$products=$items->map(fn($i)=>$i->variant?->product_id)->filter()->unique();$names=$items->pluck('product_name')->filter()->unique();
        if($variants->isEmpty()&&$products->isEmpty()&&$names->isEmpty())return collect();
        return PurchaseItem::query()->with(['purchase:id,provider_id,issue_date','purchase.provider:id,name'])->whereHas('purchase')->where(function(Builder $q)use($variants,$products,$names){$has=false;if($variants->isNotEmpty()){$q->whereIn('product_variant_id',$variants);$has=true;}if($products->isNotEmpty()){$has?$q->orWhereIn('product_id',$products):$q->whereIn('product_id',$products);$has=true;}if($names->isNotEmpty())$has?$q->orWhereIn('product_name',$names):$q->whereIn('product_name',$names);})->get();
    }

    private function historicalPurchase(InvoiceItem $item,?Product $product,Invoice $invoice,Collection $history):?PurchaseItem
    {
        return $history->filter(fn(PurchaseItem $p)=>$p->purchase?->issue_date&&$p->purchase->issue_date->lte($invoice->issue_date)&&(($item->product_variant_id&&(int)$p->product_variant_id===(int)$item->product_variant_id)||($product&&(int)$p->product_id===(int)$product->id)||($item->product_name&&mb_strtolower((string)$p->product_name)===mb_strtolower((string)$item->product_name))))->sortByDesc(fn($p)=>$p->purchase->issue_date)->first();
    }

    private function currentCost(?Product $product):float{return(float)($product?->cost_with_discount?:$product?->replacement_cost?:$product?->last_purchase_price?:0);}
    private function isCredit(Invoice $i):bool{$n=mb_strtolower((string)$i->receipt_type_name);return $i->receipt_types_prefix==='NC'||str_contains($n,'crédito')||str_contains($n,'credito');}
    private function typeLabel(Invoice $i):string{if($this->isCredit($i))return'Nota de Crédito';if($i->receipt_types_prefix==='ND'||str_contains(mb_strtolower((string)$i->receipt_type_name),'débito')||str_contains(mb_strtolower((string)$i->receipt_type_name),'debito'))return'Nota de Débito';return'Factura de Venta';}
    private function channel(Invoice $i):string{if($i->service_channel)return$i->service_channel;if($i->ecommerce_number)return'E-commerce';return match((int)$i->service_channel_id){2=>'E-commerce',3=>'Aplicación móvil',default=>'ERP'};}
    private function pointOfSale(Invoice $i):string{return$i->report_point_of_sale?:($i->arca_point_of_sale?'Punto '.$i->arca_point_of_sale:($i->first_number?'Punto '.$i->first_number:'Sin informar'));}
    private function pointsOfSale():Collection{return CashSheet::query()->whereNotNull('pos_name')->where('pos_name','!=','')->distinct()->pluck('pos_name')->merge(Invoice::query()->whereNotNull('arca_point_of_sale')->distinct()->pluck('arca_point_of_sale')->map(fn($v)=>'Punto '.$v))->merge(Invoice::query()->whereNotNull('first_number')->where('first_number','!=','')->distinct()->pluck('first_number')->map(fn($v)=>'Punto '.$v))->filter()->unique()->sort()->values();}
    private function userIdentities(Collection $users):Collection{$map=collect();$users->each(function(User $u)use($map){foreach([$u->name,$u->username,$u->email]as$id)if($id)$map->put(mb_strtolower((string)$id),$u);});return$map;}
    private function seller(Invoice $i,Collection $users,Collection $ids):?User{if($i->report_seller_user_id&&$users->has((int)$i->report_seller_user_id))return$users->get((int)$i->report_seller_user_id);foreach([$i->user_name,$i->seller_full_name]as$id)if($id&&$ids->has(mb_strtolower($id)))return$ids->get(mb_strtolower($id));return null;}
    private function variantLabel(InvoiceItem $i):string{$parts=collect([$i->size_name?:$i->variant?->size?->name,$i->color_name?:$i->variant?->color?->name,$i->variant?->sku])->filter()->unique();return$parts->isEmpty()?'Sin variante':$parts->implode(' · ');}
    private function paymentMethods(Invoice $i):string{$labels=$i->payments->map(fn($p)=>match($p->payment_method){'cash'=>'Efectivo','credit_card'=>'Tarjeta de crédito','debit_card'=>'Tarjeta de débito','transfer'=>'Transferencia','current_account','checking_account'=>'Cuenta corriente',default=>$p->payment_method?ucfirst(str_replace('_',' ',$p->payment_method)):'Sin informar'})->unique();return$labels->isEmpty()?($this->isCredit($i)?'No aplica':'Sin informar'):$labels->implode(' + ');}
    private function financing(Invoice $i):string{$plans=$i->payments->map(fn($p)=>$p->card_plan?:($p->payment_method==='current_account'?($i->payment_condition_name?:'Cuenta corriente'):null))->filter()->unique();return$plans->isEmpty()?'—':$plans->implode(' + ');}
}
