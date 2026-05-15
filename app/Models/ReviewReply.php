<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReply extends Model
{
    protected $table = 'review_replies';
    protected $primaryKey = 'reply_id';
    
    protected $fillable = [
        'review_id',
        'user_id',
        'reply_text'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'review_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}