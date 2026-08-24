<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
