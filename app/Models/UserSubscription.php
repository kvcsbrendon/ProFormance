<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $table = 'user_subscriptions';
    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
        'admin_note',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'Active' && $this->expires_at->isFuture();
    }

    public static function activeFor(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'Active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();
    }
}
