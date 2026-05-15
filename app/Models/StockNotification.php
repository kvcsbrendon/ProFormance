<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockNotification extends Model
{
    protected $table      = 'stock_notifications';
    protected $primaryKey = 'notification_id';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'email',
        'user_id',
        'notified',
        'created_at',
        'notified_at',
    ];

    protected $casts = [
        'notified'    => 'boolean',
        'created_at'  => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
