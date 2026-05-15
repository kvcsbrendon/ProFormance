<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    
    protected $fillable = [
        'order_id',
        'variant_id',
        'sku',
        'title',
        'unit_price_penny',
        'quantity',
        'tax_rate',
        'tax_penny',
        'is_gift'
    ];
    
    protected $casts = [
        'is_gift' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
    
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }
}