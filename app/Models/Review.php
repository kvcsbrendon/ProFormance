<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'user_id', 'product_id', 'rating', 'title', 'body', 
        'is_approved', 'is_verified_purchase', 'admin_reply'
    ];

    protected $casts = [
        'rating'               => 'integer',
        'is_approved'          => 'boolean',
        'is_verified_purchase' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class, 'review_id', 'review_id');
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpfulVote::class, 'review_id', 'review_id');
    }

    public function reports()
    {
        return $this->hasMany(ReviewReport::class, 'review_id', 'review_id');
    }
}