<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    public $timestamps = false;
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'country_phone_code',
        'phone_number',
        'user_role',
        'is_active',
        'email_verified',
        'google_id',
    ];

    protected $hidden = [
        'password_hash',
        'password_salt',
        'remember_token'
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];
    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getEmailAttribute()
    {
        return $this->loginDetail ? $this->loginDetail->email_address : null;
    }

    public function getAuthPassword()
    {
        return $this->loginDetail ? $this->loginDetail->password_hash : null;
    }

    public function loginDetail()
    {
        return $this->hasOne(LoginDetail::class, 'user_id', 'user_id');
    }
}