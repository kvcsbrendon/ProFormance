<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantCurrencyPrice extends Model
{
    protected $table = 'variant_currency_prices';
    
    protected $primaryKey = ['variant_id', 'currency_code'];
    public $incrementing = false;
    public $timestamps = true;
    
    protected $fillable = [
        'variant_id',
        'currency_code',
        'price_penny',
        'was_price_penny'
    ];
    
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'currency_code');
    }
    
    public function getPriceAttribute()
    {
        return $this->price_penny / 100;
    }
    
    public function getWasPriceAttribute()
    {
        return $this->was_price_penny ? $this->was_price_penny / 100 : null;
    }
}