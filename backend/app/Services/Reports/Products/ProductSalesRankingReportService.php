<?php
namespace App\Services\Reports\Products;
use App\Models\Products\Color;
use App\Models\Products\Size;
use App\Services\Reports\Sales\DetailedSalesReportService;
use Illuminate\Support\Collection;
/** Construye el Ranking de Productos Vendidos sobre el detalle de ventas netas. */
class ProductSalesRankingReportService
{
    public function __construct(private readonly DetailedSalesReportService $details){}
    public function generate(array $filters):array
    {
        $detail=$this->details->generate(['date_from'=>$filters['date_from'],'date_to'=>$filters['date_to'],'with_variant'=>(bool)$filters['with_variant']]);
        $rows=collect($detail['rows'])
            ->when($filters['category_id']??null,fn($r,$v)=>$r->where('category_id',(int)$v))->when($filters['color']??null,fn($r,$v)=>$r->filter(fn($x)=>mb_stripos($x['color'],$v)!==false))
            ->when($filters['size']??null,fn($r,$v)=>$r->filter(fn($x)=>mb_stripos($x['size'],$v)!==false))->when($filters['provider_id']??null,fn($r,$v)=>$r->where('provider_id',(int)$v))
            ->when($filters['brand_id']??null,fn($r,$v)=>$r->where('brand_id',(int)$v))->when($filters['model_id']??null,fn($r,$v)=>$r->where('model_id',(int)$v));
        $listBy=$filters['list_by'];$grouped=$rows->groupBy(fn($r)=>$this->key($r,$listBy,(bool)$filters['with_variant']))->map(function(Collection $items)use($listBy,$filters){
            $first=$items->first();return['code'=>$listBy==='product'?$first['internal_code']:'—','product'=>$this->label($first,$listBy).($filters['with_variant']?' · '.$first['variant']:''),
                'brand'=>$this->dimension($items,'brand'),'model'=>$this->dimension($items,'model'),'category'=>$this->dimension($items,'category'),'provider'=>$this->dimension($items,'provider'),'total_sale'=>round((float)$items->sum('total'),2),
                'units'=>round((float)$items->sum('quantity'),4),'operations'=>$items->pluck('invoice_id')->unique()->count(),'profit'=>round((float)$items->sum('profit'),2)];
        })->sortByDesc($filters['order_by'])->take((int)$filters['top'])->values();
        return ['filters'=>$filters,'rows'=>$grouped->all(),'summary'=>['records'=>$grouped->count(),'total_sale'=>round((float)$grouped->sum('total_sale'),2),'units'=>round((float)$grouped->sum('units'),4),'profit'=>round((float)$grouped->sum('profit'),2)]];
    }
    public function options():array{$o=$this->details->options();return array_merge($o,['sizes'=>Size::query()->orderBy('name')->pluck('name'),'colors'=>Color::query()->orderBy('name')->pluck('name')]);}
    public function exportRows(array $report):Collection{return collect($report['rows'])->map(fn($r)=>[$r['code'],$r['product'],$r['brand'],$r['model'],$r['category'],$r['provider'],$r['total_sale'],$r['units'],$r['operations'],$r['profit']]);}
    private function key(array $r,string $by,bool $variant):string{$base=(string)($r[$by.'_id']??$r[$by]??$r['product_id']??$r['product']);return$variant?$base.'|'.$r['variant']:$base;}
    private function label(array $r,string $by):string{return match($by){'category'=>$r['category'],'brand'=>$r['brand'],'model'=>$r['model'],'provider'=>$r['provider'],default=>$r['product']};}
    private function dimension(Collection $items,string $field):string{$values=$items->pluck($field)->filter()->unique()->values();return$values->count()===1?$values->first():'Varios';}
}
