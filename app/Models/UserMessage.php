<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMessage extends Model
{
    protected $table = 'user_messages';
    protected $primaryKey = 'message_id';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'body',
        'is_read',
        'link_url',
        'link_label',
        'created_at',
        'read_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
        'read_at'    => 'datetime',
    ];

    // Category constants
    const CAT_ORDER       = 'order';
    const CAT_SECURITY    = 'security';
    const CAT_PROMOTIONAL = 'promotional';
    const CAT_SYSTEM      = 'system';

    const CATEGORIES = [
        self::CAT_ORDER       => 'Orders',
        self::CAT_SECURITY    => 'Security',
        self::CAT_PROMOTIONAL => 'Promotional',
        self::CAT_SYSTEM      => 'System',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Send a message to a user.
     */
    public static function send(int $userId, string $category, string $title, string $body, ?string $linkUrl = null, ?string $linkLabel = null): self
    {
        return static::create([
            'user_id'    => $userId,
            'category'   => $category,
            'title'      => $title,
            'body'       => $body,
            'link_url'   => $linkUrl,
            'link_label' => $linkLabel,
            'created_at' => now(),
        ]);
    }
}
