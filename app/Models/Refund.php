<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'refunds';
    protected $primaryKey = 'refund_id';

    protected $fillable = [
        'order_id',
        'payment_id',
        'amount_penny',
        'refund_status',
        'reason',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function items()
    {
        return $this->hasMany(RefundItem::class, 'refund_id', 'refund_id');
    }
}
