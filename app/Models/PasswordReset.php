<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'created_at',
        'used',
    ];

    protected $casts = [
        'used' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->created_at->addMinutes(15)->isPast();
    }

    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }
}
