<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxProfile extends Model
{
    public $timestamps = false;
    protected $table = 'user_tax_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'vat_number',
        'tax_exempt',
        'tax_validation_date',
        'created_at',
    ];
}
