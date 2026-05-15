<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeSaveItem extends Model
{
    protected $table = 'subscribe_save_items';
    protected $primaryKey = 'ss_item_id';

    protected $fillable = [
        'user_id',
        'variant_id',
        'quantity',
        'frequency_weeks',
        'next_delivery_at',
        'is_active',
        'suspended_at',
        'admin_note',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'next_delivery_at' => 'date',
        'suspended_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public static function activeFor(int $userId)
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->with('variant.product')
            ->get();
    }

    public static function isSubscribed(int $userId, int $variantId): ?self
    {
        return static::where('user_id', $userId)
            ->where('variant_id', $variantId)
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->first();
    }

    public function isSuspended(): bool
    {
        return $this->is_active && $this->suspended_at !== null;
    }

    public function frequencyLabel(): string
    {
        $w = $this->frequency_weeks;
        if ($w == 1) return 'Every week';
        if ($w == 2) return 'Every 2 weeks';
        if ($w == 4) return 'Every month';
        if ($w == 8) return 'Every 2 months';
        if ($w == 12) return 'Every 3 months';
        return "Every {$w} weeks";
    }
}
