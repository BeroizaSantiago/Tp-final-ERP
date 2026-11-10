<?php

namespace App\Services;

use App\Models\Clients\Client;
use App\Models\Products\Product;
use App\Models\Purchases\Provider;
use App\Models\Stock\InventoryItem;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CLASSIFICATIONS = ['new', 'alive', 'dormant', 'dead'];

    public function generate(): array
    {
        $today = today();
        $ninetyDaysAgo = $today->copy()->subDays(90);
        $yearAgo = $today->copy()->subDays(365);
        $products = Product::query()
            ->get(['id', 'external_id', 'code', 'name', 'is_active', 'created_at']);

        $lastSales = $this->lastSalesByProduct();
        $stock = $this->stockByProduct();
        $classified = $products->map(function (Product $product) use ($lastSales, $stock, $ninetyDaysAgo, $yearAgo, $today) {
            $lastSale = $lastSales->get($product->id);
            $stockData = $stock->get($product->id, ['units' => 0, 'value' => 0]);
            $classification = $this->classify($product, $lastSale, $ninetyDaysAgo, $yearAgo);

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'active' => (bool) $product->is_active,
                'classification' => $classification,
                'created_at' => $product->created_at?->toDateString(),
                'last_sale_at' => $lastSale?->toDateString(),
                'days_without_sales' => $lastSale ? $lastSale->diffInDays($today) : null,
                'stock_units' => round((float) $stockData['units'], 2),
                'stock_value' => round((float) $stockData['value'], 2),
            ];
        });

        $classification = collect(self::CLASSIFICATIONS)->mapWithKeys(function (string $key) use ($classified) {
            $rows = $classified->where('classification', $key);

            return [$key => [
                'products' => $rows->count(),
                'stock_units' => round((float) $rows->sum('stock_units'), 2),
                'stock_value' => round((float) $rows->sum('stock_value'), 2),
            ]];
        });

        $monthInvoices = $this->salesInvoices()->whereBetween('issue_date', [now()->startOfMonth(), now()->endOfMonth()]);
        $todayInvoices = $this->salesInvoices()->whereDate('issue_date', $today);
        $monthTotal = (float) (clone $monthInvoices)->sum('total_amount');
        $monthTransactions = (int) (clone $monthInvoices)->count();
        $periodInvoices = $this->salesInvoices()->whereDate('issue_date', '>=', today()->subDays(29));
        $periodTotal = (float) (clone $periodInvoices)->sum('total_amount');
        $periodTransactions = (int) (clone $periodInvoices)->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'period' => [
                'from' => today()->subDays(29)->toDateString(),
                'to' => today()->toDateString(),
                'label' => 'Últimos 30 días',
            ],
            'summary' => [
                'sales_today' => round((float) (clone $todayInvoices)->sum('total_amount'), 2),
                'transactions_today' => (int) (clone $todayInvoices)->count(),
                'sales_period' => round($periodTotal, 2),
                'transactions_period' => $periodTransactions,
                'sales_month' => round($monthTotal, 2),
                'average_ticket_month' => round($monthTransactions ? $monthTotal / $monthTransactions : 0, 2),
                'active_products' => $products->where('is_active', true)->count(),
                'clients' => Client::query()->count(),
                'providers' => Provider::query()->count(),
                'stock_units' => round((float) $stock->sum('units'), 2),
                'stock_value' => round((float) $stock->sum('value'), 2),
                'low_stock_items' => Product::query()
                    ->where('is_active', true)
                    ->where('min_stock', '>', 0)
                    ->whereColumn('current_stock', '<=', 'min_stock')
                    ->count(),
                'recent_purchases' => DB::table('purchases')->whereDate('issue_date', '>=', today()->subDays(29))->count(),
                'pending_returns' => DB::table('book_return_sales')->where('status', 'pending')->count()
                    + DB::table('book_return_purchases')->where('status', 'pending')->count(),
            ],
            'sales' => $this->salesTrend(),
            'top_products' => $this->topProducts(),
            'low_stock_products' => $this->lowStockProducts(),
            'recent_purchases' => $this->recentPurchases(),
            'pending_returns' => $this->pendingReturns(),
            'publisher_volume' => $this->publisherVolume(),
            'classification' => $classification,
            'classification_definitions' => [
                'new' => 'Alta hace menos de 90 días; todavía sin historial suficiente.',
                'alive' => 'Registró ventas en los últimos 90 días.',
                'dormant' => 'Su última venta fue entre 90 y 365 días atrás.',
                'dead' => 'No vendió hace más de 365 días o nunca registró ventas.',
            ],
            'attention_products' => $classified
                ->whereIn('classification', ['dead', 'dormant'])
                ->sortByDesc('stock_value')
                ->take(8)
                ->values(),
        ];
    }

    private function classify(Product $product, ?Carbon $lastSale, Carbon $ninetyDaysAgo, Carbon $yearAgo): string
    {
        if ($product->created_at && $product->created_at->greaterThanOrEqualTo($ninetyDaysAgo)) {
            return 'new';
        }

        if ($lastSale && $lastSale->greaterThanOrEqualTo($ninetyDaysAgo)) {
            return 'alive';
        }

        if ($lastSale && $lastSale->greaterThanOrEqualTo($yearAgo)) {
            return 'dormant';
        }

        return 'dead';
    }

    private function salesInvoices(): Builder
    {
        return DB::table('invoices')
            ->whereRaw("LOWER(COALESCE(status_name, '')) NOT LIKE '%anul%'")
            ->where(function (Builder $query) {
                $query->where('receipt_types_prefix', 'FV')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereNull('receipt_types_prefix')
                            ->where('receipt_type_name', 'like', '%Factura%')
                            ->where('receipt_type_name', 'not like', '%Compra%');
                    });
            });
    }

    private function lastSalesByProduct(): Collection
    {
        return DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->leftJoin('product_variants as variant', 'variant.id', '=', 'item.product_variant_id')
            ->leftJoin('products as product', function ($join) {
                $join->on('product.id', '=', DB::raw('COALESCE(item.product_id, variant.product_id)'))
                    ->orOn(function ($fallback) {
                        $fallback->on('product.external_id', '=', 'item.product_external_id')
                            ->whereNull('item.product_id')
                            ->whereNull('variant.product_id');
                    });
            })
            ->whereNotNull('product.id')
            ->whereRaw("LOWER(COALESCE(invoice.status_name, '')) NOT LIKE '%anul%'")
            ->where(function ($query) {
                $query->where('invoice.receipt_types_prefix', 'FV')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('invoice.receipt_types_prefix')
                            ->where('invoice.receipt_type_name', 'like', '%Factura%')
                            ->where('invoice.receipt_type_name', 'not like', '%Compra%');
                    });
            })
            ->groupBy('product.id')
            ->selectRaw('product.id as product_id, MAX(invoice.issue_date) as last_sale_at')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->product_id => Carbon::parse($row->last_sale_at)]);
    }

    private function stockByProduct(): Collection
    {
        return DB::table('inventory_items as inventory')
            ->leftJoin('product_variants as variant', 'variant.id', '=', 'inventory.product_variant_id')
            ->leftJoin('products as product', 'product.id', '=', 'inventory.product_id')
            ->whereNotNull('inventory.product_id')
            ->groupBy('inventory.product_id')
            ->selectRaw("inventory.product_id,
                SUM(inventory.current_stock) as units,
                SUM(inventory.current_stock * COALESCE(
                    NULLIF(variant.price_a_with_tax, 0),
                    NULLIF(inventory.valued_item, 0),
                    NULLIF(product.cost_with_discount, 0),
                    NULLIF(product.replacement_cost, 0),
                    NULLIF(product.last_purchase_price, 0),
                    0
                )) as stock_value")
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->product_id => [
                'units' => (float) $row->units,
                'value' => (float) $row->stock_value,
            ]]);
    }

    private function salesTrend(): array
    {
        $from = today()->subDays(29);
        $rows = $this->salesInvoices()
            ->whereDate('issue_date', '>=', $from)
            ->groupByRaw('DATE(issue_date)')
            ->selectRaw('DATE(issue_date) as sale_date, SUM(total_amount) as total, COUNT(*) as transactions')
            ->get()->keyBy('sale_date');

        $days = collect(range(0, 29))->map(fn (int $offset) => $from->copy()->addDays($offset));

        return [
            'labels' => $days->map(fn (Carbon $date) => $date->format('d/m'))->all(),
            'totals' => $days->map(fn (Carbon $date) => round((float) ($rows->get($date->toDateString())->total ?? 0), 2))->all(),
            'transactions' => $days->map(fn (Carbon $date) => (int) ($rows->get($date->toDateString())->transactions ?? 0))->all(),
        ];
    }

    private function topProducts(): Collection
    {
        return DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->leftJoin('product_variants as variant', 'variant.id', '=', 'item.product_variant_id')
            ->leftJoin('products as product', function ($join) {
                $join->on('product.id', '=', DB::raw('COALESCE(item.product_id, variant.product_id)'))
                    ->orOn(function ($fallback) {
                        $fallback->on('product.external_id', '=', 'item.product_external_id')
                            ->whereNull('item.product_id')
                            ->whereNull('variant.product_id');
                    });
            })
            ->whereDate('invoice.issue_date', '>=', today()->subDays(29))
            ->whereNotNull('product.id')
            ->whereRaw("LOWER(COALESCE(invoice.status_name, '')) NOT LIKE '%anul%'")
            ->where(fn ($query) => $query->where('invoice.receipt_types_prefix', 'FV')
                ->orWhere(fn ($legacy) => $legacy->whereNull('invoice.receipt_types_prefix')->where('invoice.receipt_type_name', 'like', '%Factura%')->where('invoice.receipt_type_name', 'not like', '%Compra%')))
            ->groupBy('product.id', 'product.name', 'product.code')
            ->orderByDesc('units')
            ->limit(7)
            ->selectRaw('product.id, product.name, product.code, SUM(item.quantity) as units, SUM(item.total_amount) as amount')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'units' => round((float) $row->units, 2),
                'amount' => round((float) $row->amount, 2),
            ]);
    }

    private function lowStockProducts(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->limit(8)
            ->get(['id', 'name', 'code', 'current_stock', 'min_stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'stock' => (float) $product->current_stock,
                'minimum' => (float) $product->min_stock,
            ]);
    }

    private function recentPurchases(): Collection
    {
        return DB::table('purchases')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'full_number', 'provider_name', 'issue_date', 'total_amount', 'status_name'])
            ->map(fn ($purchase) => [
                'id' => (int) $purchase->id,
                'number' => $purchase->full_number ?: 'Compra #'.$purchase->id,
                'provider' => $purchase->provider_name ?: 'Sin proveedor',
                'date' => $purchase->issue_date,
                'total' => (float) $purchase->total_amount,
                'status' => $purchase->status_name,
            ]);
    }

    private function pendingReturns(): array
    {
        $customerCount = DB::table('book_return_sales')->where('status', 'pending')->count();
        $supplierCount = DB::table('book_return_purchases')->where('status', 'pending')->count();
        $customers = DB::table('book_return_sales as returns')
            ->leftJoin('clients', 'clients.id', '=', 'returns.client_id')
            ->where('returns.status', 'pending')
            ->orderByDesc('returns.return_date')
            ->limit(5)
            ->get(['returns.id', 'returns.return_date', 'returns.reason', 'clients.name'])
            ->map(fn ($return) => [
                'id' => (int) $return->id,
                'type' => 'customer',
                'date' => $return->return_date,
                'party' => $return->name ?: 'Cliente',
                'reason' => $return->reason,
            ]);
        $suppliers = DB::table('book_return_purchases as returns')
            ->leftJoin('providers', 'providers.id', '=', 'returns.provider_id')
            ->where('returns.status', 'pending')
            ->orderByDesc('returns.return_date')
            ->limit(5)
            ->get(['returns.id', 'returns.return_date', 'returns.reason', 'providers.name'])
            ->map(fn ($return) => [
                'id' => (int) $return->id,
                'type' => 'supplier',
                'date' => $return->return_date,
                'party' => $return->name ?: 'Proveedor',
                'reason' => $return->reason,
            ]);

        return [
            'customer_count' => $customerCount,
            'supplier_count' => $supplierCount,
            'total' => $customerCount + $supplierCount,
            'items' => $customers->concat($suppliers)->sortByDesc('date')->take(6)->values(),
        ];
    }

    private function publisherVolume(): Collection
    {
        return DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->leftJoin('product_variants as variant', 'variant.id', '=', 'item.product_variant_id')
            ->leftJoin('products as product', 'product.id', '=', DB::raw('COALESCE(item.product_id, variant.product_id)'))
            ->leftJoin('publishers as publisher', 'publisher.id', '=', DB::raw('COALESCE(product.publisher_id, variant.publisher_id)'))
            ->whereDate('invoice.issue_date', '>=', today()->subDays(29))
            ->whereRaw("LOWER(COALESCE(invoice.status_name, '')) NOT LIKE '%anul%'")
            ->where(fn ($query) => $query->where('invoice.receipt_types_prefix', 'FV')
                ->orWhere(fn ($legacy) => $legacy->whereNull('invoice.receipt_types_prefix')->where('invoice.receipt_type_name', 'like', '%Factura%')->where('invoice.receipt_type_name', 'not like', '%Compra%')))
            ->groupByRaw("COALESCE(publisher.id, 0), COALESCE(publisher.name, 'Sin editorial')")
            ->orderByDesc('amount')
            ->limit(6)
            ->selectRaw("COALESCE(publisher.id, 0) as id, COALESCE(publisher.name, 'Sin editorial') as name, SUM(item.quantity) as units, SUM(item.total_amount) as amount")
            ->get()
            ->map(fn ($publisher) => [
                'id' => (int) $publisher->id,
                'name' => $publisher->name,
                'units' => round((float) $publisher->units, 2),
                'amount' => round((float) $publisher->amount, 2),
            ]);
    }
}
