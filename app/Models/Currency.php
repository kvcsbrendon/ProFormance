<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currencies';
    protected $primaryKey = 'currency_code';
    public $incrementing = false;
    public $timestamps = true;
    
    protected $fillable = [
        'currency_code',
        'currency_name',
        'symbol',
        'is_active'
    ];
    
    public function variantPrices()
    {
        return $this->hasMany(VariantCurrencyPrice::class, 'currency_code', 'currency_code');
    }
}