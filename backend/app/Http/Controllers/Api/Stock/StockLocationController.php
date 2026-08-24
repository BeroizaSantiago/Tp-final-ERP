<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\Branch;

class StockLocationController extends Controller
{
    public function index()
    {
        return Branch::query()
            ->with(['warehouses' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name')])
            ->where('is_active', true)
            ->whereHas('warehouses', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
