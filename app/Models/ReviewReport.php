<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReport extends Model
{
    protected $table = 'review_reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'review_id',
        'user_id',
        'reason',
        'status',
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