<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $table = 'shipping_rates';
    protected $primaryKey = 'rate_id';

    protected $fillable = [
        'zone_name',
        'country_code',
        'method_key',
        'method_label',
        'price_penny',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Look up the shipping cost for a country + method.
     * Falls back to 'INTL' zone if no country-specific rate.
     */
    public static function getPenny(string $countryCode, string $method = 'standard'): int
    {
        $countryCode = strtoupper(trim($countryCode));
        $method = strtolower(trim($method));

        // Try exact country match
        $rate = static::where('country_code', $countryCode)
            ->where('method_key', $method)
            ->where('is_active', true)
            ->first();

        if ($rate) {
            return $rate->price_penny;
        }

        // Fallback to international
        $rate = static::where('country_code', 'INTL')
            ->where('method_key', $method)
            ->where('is_active', true)
            ->first();

        return $rate ? $rate->price_penny : 1999; // Ultimate fallback
    }

    /**
     * Get all active methods for a country (for checkout display).
     */
    public static function methodsFor(string $countryCode): \Illuminate\Support\Collection
    {
        $countryCode = strtoupper(trim($countryCode));

        $rates = static::where('country_code', $countryCode)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($rates->isEmpty()) {
            $rates = static::where('country_code', 'INTL')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return $rates;
    }
}
