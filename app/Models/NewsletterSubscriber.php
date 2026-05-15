<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $table = 'newsletter_subscribers';
    protected $primaryKey = 'subscriber_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id','email_address','status','subscribed_at','unsubscribed_at'
    ];

    public const STATUS_SUBSCRIBED = 'Subscribed';
    public const STATUS_UNSUBSCRIBED = 'Unsubscribed';
}
