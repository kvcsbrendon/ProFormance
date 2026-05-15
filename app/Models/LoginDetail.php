<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginDetail extends Model
{
    public $timestamps = false;
    protected $table = 'login_details';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'email_address',
        'password_hash',
        'password_salt'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}