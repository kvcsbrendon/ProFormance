<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'name',
        'monthly_price_penny',
        'free_shipping',
        'order_discount_percent',
        'is_active',
    ];

    protected $casts = [
        'free_shipping' => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id', 'plan_id');
    }

    public static function activePlan()
    {
        return static::where('is_active', true)->first();
    }
}
