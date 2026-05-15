<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContact extends Model
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'query_id';
    public $timestamps = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'email_address',
        'subject_select',
        'contact_status',
        'message_description',
        'product_search',
        'order_id',
        'product_id',
        'variant_id',
    ];

    public const SUBJECT_GENERAL  = 'General';
    public const SUBJECT_SUPPORT  = 'Support';
    public const SUBJECT_FEEDBACK = 'Feedback';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_SOLVED  = 'Solved';
}
