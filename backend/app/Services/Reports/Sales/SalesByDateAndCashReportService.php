<?php
namespace App\Services\Reports\Sales;
use App\Models\Finance\CashBox;
use App\Models\Sales\InvoicePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
/** Agrupa los cobros de ventas por fecha, caja, cajero y medio de pago. */
class SalesByDateAndCashReportService
{
    public function generate(array $filters):array
    {
        $payments=$this->payments($filters)->get();
        $entries=$payments->map(function(InvoicePayment $payment){
            $movement=$payment->cashMovements->where('status','active')->whereNull('voided_at')->sortBy('id')->first();
            $invoice=$payment->invoice;$sheet=$movement?->cashSheet;$box=$movement?->cashBox;
            $date=($movement?->created_at?:$invoice?->issue_date?:$payment->created_at)?->toDateString();
            $amount=$payment->payment_method==='current_account'?(float)$payment->amount:(float)$payment->total_paid;
            return ['date'=>$date,'cash_box_id'=>$box?->id?:$sheet?->cash_box_id,'cash_box'=>$box?->name?:$sheet?->cash_box_name?:'Sin caja',
                'cashier'=>$movement?->user?->name?:$sheet?->cashier_name?:$invoice?->seller_full_name?:$invoice?->user_name?:'Sin cajero',
                'payment_method'=>$this->paymentLabel($payment->payment_method),'invoice_id'=>$invoice?->id,'amount'=>round($amount,2)];
        })->filter(fn($row)=>$row['date']&&$row['date']>=$filters['date_from']&&$row['date']<=$filters['date_to'])
          ->when($filters['cash_box_ids']??null,fn(Collection $rows,array $ids)=>$rows->whereIn('cash_box_id',array_map('intval',$ids)))->values();

        $dates=$entries->groupBy('date')->sortKeys()->map(function(Collection $day,string $date){
            $boxes=$day->groupBy('cash_box')->map(function(Collection $boxRows,string $boxName){
                $cashiers=$boxRows->groupBy('cashier')->map(function(Collection $cashierRows,string $cashier){
                    $methods=$cashierRows->groupBy('payment_method')->map(fn(Collection $methodRows,string $method)=>[
                        'payment_method'=>$method,'operations'=>$methodRows->pluck('invoice_id')->filter()->unique()->count(),'total'=>round((float)$methodRows->sum('amount'),2),
                    ])->sortBy('payment_method')->values()->all();
                    return ['cashier'=>$cashier,'methods'=>$methods,'operations'=>$cashierRows->pluck('invoice_id')->filter()->unique()->count(),'total'=>round((float)$cashierRows->sum('amount'),2)];
                })->sortKeys()->values()->all();
                return ['cash_box'=>$boxName,'cashiers'=>$cashiers,'operations'=>$boxRows->pluck('invoice_id')->filter()->unique()->count(),'total'=>round((float)$boxRows->sum('amount'),2)];
            })->sortKeys()->values()->all();
            return ['date'=>$date,'boxes'=>$boxes,'operations'=>$day->pluck('invoice_id')->filter()->unique()->count(),'total'=>round((float)$day->sum('amount'),2)];
        })->values()->all();

        return ['filters'=>$filters,'dates'=>$dates,'summary'=>['operations'=>$entries->pluck('invoice_id')->filter()->unique()->count(),'total'=>round((float)$entries->sum('amount'),2)]];
    }

    public function options():array{return['cash_boxes'=>CashBox::query()->where('is_active',true)->orderBy('name')->get(['id','name','branch_name','box_type_name'])];}

    public function exportRows(array $report):Collection
    {
        $rows=collect();foreach($report['dates']as$day){foreach($day['boxes']as$box){foreach($box['cashiers']as$cashier){foreach($cashier['methods']as$method)$rows->push([$day['date'],$box['cash_box'],$cashier['cashier'],$method['payment_method'],$method['operations'],$method['total'],'Detalle']);}
            $rows->push([$day['date'],$box['cash_box'],'','Subtotal caja',$box['operations'],$box['total'],'Subtotal Caja']);}
            $rows->push([$day['date'],'','','Total diario',$day['operations'],$day['total'],'Total Diario']);}
        $rows->push(['','','','TOTAL GENERAL',$report['summary']['operations'],$report['summary']['total'],'Total General']);return$rows;
    }

    private function payments(array $filters):Builder
    {
        return InvoicePayment::query()->with(['invoice:id,issue_date,receipt_types_prefix,receipt_type_name,status_name,seller_full_name,user_name',
            'cashMovements.cashBox:id,name','cashMovements.cashSheet:id,cash_box_id,cash_box_name,cashier_name','cashMovements.user:id,name'])
            ->whereHas('invoice',fn(Builder $q)=>$q->where(function(Builder $types){$types->where('receipt_types_prefix','FV')->orWhere(function(Builder $legacy){$legacy->whereNull('receipt_types_prefix')->where('receipt_type_name','like','%Factura%')->where('receipt_type_name','not like','%Compra%');});})->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'"))
            ->where(function(Builder $q)use($filters){$q->whereHas('cashMovements',fn(Builder $m)=>$m->whereDate('created_at','>=',$filters['date_from'])->whereDate('created_at','<=',$filters['date_to']))->orWhereHas('invoice',fn(Builder $i)=>$i->whereDate('issue_date','>=',$filters['date_from'])->whereDate('issue_date','<=',$filters['date_to']));})
            ->orderBy('created_at')->orderBy('id');
    }

    private function paymentLabel(?string $method):string{return match($method){'cash'=>'Efectivo','credit_card'=>'Tarjeta de Crédito','debit_card'=>'Tarjeta de Débito','transfer'=>'Transferencia','current_account','checking_account'=>'Cuenta Corriente','check','cheque'=>'Cheques','qr'=>'QR','mercado_pago_qr'=>'Mercado Pago QR','voucher'=>'Voucher','deposit'=>'Depósito',default=>$method?ucfirst(str_replace('_',' ',$method)):'Sin informar'};}
}
