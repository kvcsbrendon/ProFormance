<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table      = 'product_variants';
    protected $primaryKey = 'variant_id';

    public $incrementing = true;
    protected $keyType   = 'int';
    public $timestamps   = true;

    protected $fillable = [
        'product_id',
        'sku',
        'stock_quantity',
        'barcode',
        'title',
        'options_key',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
    
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'variant_id', 'variant_id');
    }
    
    public function getPrimaryImage()
    {
        return $this->images()->orderBy('sort_order', 'asc')->first();
    }
    
    public function prices()
    {
        return $this->hasMany(VariantCurrencyPrice::class, 'variant_id', 'variant_id');
    }

    public function priceForCurrency(?string $currencyCode = null)
    {
        $currencyCode ??= session('currency', 'GBP');

        return $this->prices()
            ->where('currency_code', strtoupper($currencyCode))
            ->first();
    }


    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'variant_id', 'variant_id');
    }

    public function getPrice($currencyCode = null)
    {
        if (!$currencyCode) {
            $currencyCode = session('currency', 'GBP');
        }
        
        $priceRecord = $this->prices()
            ->where('currency_code', $currencyCode)
            ->first();
        
        if ($priceRecord) {
            return $priceRecord->price_penny / 100;
        }
        
        if ($currencyCode !== 'GBP') {
            $priceRecord = $this->prices()
                ->where('currency_code', 'GBP')
                ->first();
                
            if ($priceRecord) {
                return $priceRecord->price_penny / 100;
            }
        }
        
        return 0;
    }

    public function options()
    {
        return $this->belongsToMany(
            VariantOption::class,
            'variant_combinations',
            'variant_id',
            'option_id'
        );
    }
}
