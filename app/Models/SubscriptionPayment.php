<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'plan_id',
        'amount_penny',
        'currency_code',
        'payment_method',
        'card_id',
        'provider_ref',
        'status',
        'period_start',
        'period_end',
        'note',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    // ─── Relationships ───

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id', 'subscription_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }

    public function savedCard()
    {
        return $this->belongsTo(SavedCard::class, 'card_id', 'card_id');
    }

    // ─── Helpers ───

    /**
     * Format amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
        $sym = $symbols[$this->currency_code] ?? $this->currency_code . ' ';
        return $sym . number_format($this->amount_penny / 100, 2);
    }

    /**
     * Billing period as readable string.
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->period_start->format('d M Y') . ' – ' . $this->period_end->format('d M Y');
    }

    /**
     * Status badge CSS class.
     */
    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Paid'     => 'kb-badge-success',
            'Refunded' => 'kb-badge-warning',
            'Failed'   => 'kb-badge-danger',
            default    => 'kb-badge-secondary',
        };
    }
}
