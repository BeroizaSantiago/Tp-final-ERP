<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
