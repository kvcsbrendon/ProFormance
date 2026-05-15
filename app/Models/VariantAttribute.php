<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantAttribute extends Model
{
    protected $table      = 'variant_attributes';
    protected $primaryKey = 'attribute_id';

    public $incrementing = true;
    protected $keyType   = 'int';
    public $timestamps   = false;

    protected $fillable = [
        'attribute_name',
        'display_name',
        'selection_order',
        'is_active',
    ];

    public function options()
    {
        return $this->hasMany(VariantOption::class, 'attribute_id', 'attribute_id');
    }
}
