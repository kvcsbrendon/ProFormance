<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    protected $table = 'wishlists';
    protected $primaryKey = 'wishlist_id';

    protected $fillable = [
        'user_id',
        'wishlist_name',
        'is_public',
        'slug',
        'delivery_address_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class, 'wishlists_id', 'wishlist_id');
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address_id', 'address_id');
    }

    public static function generateUniqueSlug(string $text, ?int $excludeId = null): string
    {
        $slug = Str::slug($text);

        if (empty($slug)) {
            $slug = 'wishlist-' . Str::random(6);
        }

        $original = $slug;
        $counter = 1;

        while (true) {
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('wishlist_id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getShareUrl(): ?string
    {
        if (!$this->is_public || !$this->slug) {
            return null;
        }

        return route('wishlist.shared', $this->slug);
    }
}
