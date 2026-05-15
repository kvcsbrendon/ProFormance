<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_name',
        'product_description',
        'short_description',
        'brand_id',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'product_category',
            'product_id',
            'category_id'
        );
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'product_id');
    }

    public function getPrimaryImageUrlAttribute()
    {
        $image = $this->primaryImage;
        return $image && $image->image_url ? $image->image_url : null;
    }

    public function images()
    {
        return ProductImage::whereIn('variant_id', function($query) {
            $query->select('variant_id')
                  ->from('product_variants')
                  ->where('product_id', $this->product_id);
        });
    }

    public function getPrimaryImage()
    {
        return $this->images()
            ->orderBy('sort_order', 'asc')
            ->first();
    }

    /**
     * Get the primary image through variants.
     * Orders by sort_order instead of requiring exactly 0.
     */
    public function primaryImage()
    {
        return $this->hasOneThrough(
            ProductImage::class,
            ProductVariant::class,
            'product_id',
            'variant_id',
            'product_id',
            'variant_id'
        )->orderBy('product_images.sort_order', 'asc');
    }

    public function getDefaultVariant()
    {
        return $this->activeVariants()->first() ?? $this->variants()->first();
    }

    public function bestPriceForCurrency(?string $currencyCode = null)
    {
        $currencyCode ??= session('currency', 'GBP');

        $price = VariantCurrencyPrice::query()
            ->whereHas('variant', function ($q) {
                $q->where('is_active', true)
                ->where('product_id', $this->product_id);
            })
            ->where('currency_code', strtoupper($currencyCode))
            ->orderBy('price_penny')
            ->first();

        if (! $price) {
            return null;
        }
        $currency = \App\Models\Currency::where('currency_code', $price->currency_code)->first();

        return (object) [
            'price'    => $price->price_penny / 100,
            'was'      => $price->was_price_penny ? $price->was_price_penny / 100 : null,
            'symbol'   => $currency->symbol ?? '£',
            'currency' => $price->currency_code,
        ];
    }
}