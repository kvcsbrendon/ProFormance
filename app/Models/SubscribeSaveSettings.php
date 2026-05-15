<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeSaveSettings extends Model
{
    protected $table = 'subscribe_save_settings';
    protected $primaryKey = 'setting_id';

    public $timestamps = false;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'discount_percent',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function current(): ?self
    {
        return static::first();
    }

    public static function discountPercent(): int
    {
        $settings = static::current();
        return ($settings && $settings->is_active) ? $settings->discount_percent : 0;
    }
}
