<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discounts';
    protected $primaryKey = 'discount_id';

    protected $fillable = [
        'discount_code',
        'discoun_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_user_limit',
        'min_subtotal_penny',
        'is_active',
    ];

    protected $casts = [
        'discount_value'     => 'decimal:2',
        'is_active'          => 'boolean',
        'starts_at'          => 'datetime',
        'ends_at'            => 'datetime',
    ];

    public function isValid(int $subtotalPenny, int $userId): bool|string
    {
        if (!$this->is_active) {
            return 'This discount code is no longer active.';
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return 'This discount code is not yet active.';
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return 'This discount code has expired.';
        }

        if ($this->min_subtotal_penny && $subtotalPenny < $this->min_subtotal_penny) {
            $min = number_format($this->min_subtotal_penny / 100, 2);
            return "Minimum order of £{$min} required for this code.";
        }

        if ($this->usage_limit !== null) {
            $totalUses = DiscountRedemption::where('discount_id', $this->discount_id)->count();
            if ($totalUses >= $this->usage_limit) {
                return 'This discount code has reached its usage limit.';
            }
        }

        if ($this->per_user_limit !== null) {
            $userUses = DiscountRedemption::where('discount_id', $this->discount_id)
                ->where('user_id', $userId)
                ->count();
            if ($userUses >= $this->per_user_limit) {
                return 'You have already used this discount code.';
            }
        }

        return true;
    }


    public function calculatePenny(int $subtotalPenny): int
    {
        if ($this->discoun_type === 'percentage') {
            return (int) round($subtotalPenny * ($this->discount_value / 100));
        }

        if ($this->discoun_type === 'fixed_amount') {
            return (int) round($this->discount_value * 100);
        }

        return 0;
    }
}
