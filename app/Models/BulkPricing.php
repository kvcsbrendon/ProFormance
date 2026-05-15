<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkPricing extends Model
{
    protected $table = 'bulk_pricing';
    protected $primaryKey = 'bulk_pricing_id';

    protected $fillable = [
        'variant_id',
        'currency_code',
        'min_quantity',
        'price_penny',
        'is_active',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    /**
     * Get the best price for a given quantity.
     * Returns the tier with the highest min_quantity that the qty meets.
     */
    public static function getPriceForQty(int $variantId, string $currencyCode, int $quantity): ?int
    {
        $tier = static::where('variant_id', $variantId)
            ->where('currency_code', $currencyCode)
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('min_quantity')
            ->first();

        return $tier?->price_penny;
    }

    /**
     * Get all active tiers for a variant+currency, sorted by min_quantity.
     */
    public static function getTiers(int $variantId, string $currencyCode): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('variant_id', $variantId)
            ->where('currency_code', $currencyCode)
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get();
    }
}
