<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    protected $table      = 'variant_options';
    protected $primaryKey = 'option_id';

    public $incrementing = true;
    protected $keyType   = 'int';
    public $timestamps   = false;

    protected $fillable = [
        'attribute_id',
        'variant_value',
        'display_value',
        'sort_order',
        'is_active',
    ];

    public function attribute()
    {
        return $this->belongsTo(VariantAttribute::class, 'attribute_id', 'attribute_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_combinations',
            'option_id',
            'variant_id'
        );
    }
}
