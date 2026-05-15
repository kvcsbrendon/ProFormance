<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    protected $table = 'wishlist_items';

    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'wishlists_id',
        'variant_id',
    ];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class, 'wishlists_id', 'wishlist_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }
}
