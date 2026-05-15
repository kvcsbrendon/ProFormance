<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantCombination extends Model
{
    protected $table = 'variant_combinations';
    public $incrementing = false;
    protected $primaryKey = null;

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'option_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function option()
    {
        return $this->belongsTo(VariantOption::class, 'option_id', 'option_id');
    }
}
