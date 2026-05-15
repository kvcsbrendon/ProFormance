<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table      = 'inventory';
    protected $primaryKey = 'variant_id';

    public $incrementing = false;
    protected $keyType   = 'int';
    public $timestamps   = true;

    protected $fillable = [
        'variant_id',
        'available_stock',
        'stock_allocated',
        'reorder_point',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function getInStockAttribute()
    {
        return max(0, ($this->available_stock ?? 0) - ($this->stock_allocated ?? 0));
    }
}
