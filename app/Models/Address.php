<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';
    protected $primaryKey = 'address_id';

    protected $fillable = [
        'user_id',
        'recipient_name',
        'house_number',
        'address_line_one',
        'address_line_two',
        'city',
        'county',
        'postcode',
        'country_code',
        'country_phone_code',
        'phone_number',
        'is_default_shipping_address',
        'is_default_billing_address',
    ];

    protected $casts = [
        'is_default_shipping_address' => 'boolean',
        'is_default_billing_address'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }


    public function getFormattedAttribute(): string
    {
        return collect([
            $this->house_number,
            $this->address_line_one,
            $this->address_line_two,
            $this->city,
            $this->county,
            $this->postcode,
        ])->filter()->implode(', ');
    }
}
