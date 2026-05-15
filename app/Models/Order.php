<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'order_number',
        'user_id',
        'currency_code',
        'order_status',
        'subtotal_penny',
        'shipping_penny',
        'tax_penny',
        'discount_penny',
        'total_penny',
        'ship_recipient_name',
        'ship_house_number',
        'ship_address_line_one',
        'ship_address_line_two',
        'ship_city',
        'ship_county',
        'ship_postcode',
        'ship_country_code',
        'ship_phone_number',
        'shipping_address_id',
        'bill_recipient_name',
        'bill_house_number',
        'bill_address_line_one',
        'bill_address_line_two',
        'bill_city',
        'bill_county',
        'bill_postcode',
        'bill_country_code',
        'bill_phone_number',
        'billing_address_id',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function refunds()
    {
        return $this->hasMany(\App\Models\Refund::class, 'order_id', 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(\App\Models\Payment::class, 'order_id', 'order_id');
    }

}
