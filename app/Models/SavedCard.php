<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedCard extends Model
{
    protected $table = 'saved_cards';
    protected $primaryKey = 'card_id';

    protected $fillable = [
        'user_id',
        'card_brand',
        'last_four',
        'card_name',
        'expiry_month',
        'expiry_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Detect card brand from full card number.
     */
    public static function detectBrand(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        if (preg_match('/^4/', $number)) return 'visa';
        if (preg_match('/^5[1-5]/', $number)) return 'mastercard';
        if (preg_match('/^3[47]/', $number)) return 'amex';
        if (preg_match('/^6(?:011|5)/', $number)) return 'discover';
        return 'unknown';
    }

    /**
     * Masked display: •••• •••• •••• 1234
     */
    public function getMaskedNumberAttribute(): string
    {
        return '•••• •••• •••• ' . $this->last_four;
    }

    /**
     * Expiry display: 03/27
     */
    public function getExpiryDisplayAttribute(): string
    {
        return str_pad($this->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . substr($this->expiry_year, -2);
    }

    /**
     * Check if card is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        $now = now();
        return $this->expiry_year < $now->year
            || ($this->expiry_year == $now->year && $this->expiry_month < $now->month);
    }
}
