<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $primaryKey = 'image_id';
    public $timestamps = true;
    
    protected $fillable = [
        'variant_id',
        'image_url',
        'alt_text',
        'sort_order'
    ];
    
    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'variant_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}