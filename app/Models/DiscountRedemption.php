<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountRedemption extends Model
{
    protected $table = 'discount_redemptions';
    protected $primaryKey = 'redemption_id';
    public $timestamps = false;

    protected $fillable = [
        'discount_id',
        'user_id',
        'order_id',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id', 'discount_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
