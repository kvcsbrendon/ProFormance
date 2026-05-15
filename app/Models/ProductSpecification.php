<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    protected $table      = 'product_specifications';
    protected $primaryKey = 'spec_id';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'spec_group',
        'spec_name',
        'spec_value',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
