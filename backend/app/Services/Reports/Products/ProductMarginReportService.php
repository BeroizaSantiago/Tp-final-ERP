<?php
namespace App\Services\Reports\Products;
use App\Services\Reports\Sales\DetailedSalesReportService;
use Illuminate\Support\Collection;
use InvalidArgumentException;
/** Agrupa la rentabilidad de ventas por producto, categoría o marca. */
class ProductMarginReportService
{
    public function __construct(private readonly DetailedSalesReportService $details){}
    public function generate(string $grouping,array $filters):array
    {
        if(!in_array($grouping,['product','category','brand'],true))throw new InvalidArgumentException('Agrupación de margen inválida.');
        $detailFilters=['date_from'=>$filters['date_from'],'date_to'=>$filters['date_to'],'with_variant'=>false];
        if($filters['branch']??null)$detailFilters['branch']=$filters['branch'];
        $filterKey=$grouping.'_id';if($filters[$filterKey]??null)$detailFilters[$filterKey]=$filters[$filterKey];
        $details=$this->details->generate($detailFilters);$rows=collect($details['rows']);
        $grouped=$rows->groupBy(fn($r)=>$this->groupKey($r,$grouping))->map(function(Collection $items,$key)use($grouping){
            $first=$items->first();$sale=round((float)$items->sum('total'),2);$profit=round((float)$items->sum('profit'),2);$cost=round((float)$items->sum('cost'),2);
            return ['code'=>$grouping==='product'?$first['internal_code']:'—','product'=>$this->groupLabel($first,$grouping),'total_sale'=>$sale,'profit'=>$profit,
                'average_margin'=>$sale!=0?round($profit*100/$sale,2):0.0,'average_profitability'=>$cost!=0?round($profit*100/$cost,2):0.0,'percentage'=>0.0];
        })->values();
        $totalSale=round((float)$grouped->sum('total_sale'),2);$grouped=$grouped->map(function($r)use($totalSale){$r['percentage']=$totalSale!=0?round($r['total_sale']*100/$totalSale,2):0.0;return$r;})->sortByDesc('total_sale')->values();
        return ['grouping'=>$grouping,'title'=>$this->title($grouping),'filters'=>$filters,'rows'=>$grouped->all(),'summary'=>['records'=>$grouped->count(),'total_sale'=>$totalSale,'profit'=>round((float)$grouped->sum('profit'),2)]];
    }
    public function options():array{$o=$this->details->options();return['branches'=>$o['branches'],'products'=>$o['products'],'categories'=>$o['categories'],'brands'=>$o['brands']];}
    public function exportRows(array $report):Collection{return collect($report['rows'])->map(fn($r)=>[$r['code'],$r['product'],$r['total_sale'],$r['profit'],$r['average_margin'],$r['average_profitability'],$r['percentage']]);}
    private function groupKey(array $r,string $g):string{return(string)($r[$g.'_id']?:$this->groupLabel($r,$g));}
    private function groupLabel(array $r,string $g):string{return match($g){'product'=>$r['product'],'category'=>$r['category'],'brand'=>$r['brand']};}
    private function title(string $g):string{return match($g){'product'=>'Margen por Producto','category'=>'Margen por Categoría','brand'=>'Margen por Marca'};}
}
