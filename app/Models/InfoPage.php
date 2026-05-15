<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoPage extends Model
{
    protected $table = 'info_pages';
    protected $primaryKey = 'page_id';
    
    protected $fillable = [
        'slug',
        'title',
        'intro',
        'route_name',
        'sections',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'sections' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Add a mutator to ensure sections is always an array
    public function getSectionsAttribute($value)
    {
        // If it's null, return empty array
        if (is_null($value)) {
            return [];
        }
        
        // If it's already an array (from casting), return it
        if (is_array($value)) {
            return $value;
        }
        
        // Try to decode if it's a string
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}