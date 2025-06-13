<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];    public function bookmarks()
    {
        return $this->belongsToMany(Blog::class, 'blog_user_bookmarks')->withTimestamps();
    }
    
    /**
     * Get all blog moderation records where this user is the moderator
     */
    public function blogModerations()
    {
        return $this->hasMany(BlogModeration::class, 'moderator_id');
    }
    
    /**
     * Get all travsnap moderation records where this user is the moderator
     */
    public function travsnapModerations()
    {
        return $this->hasMany(TravsnapModeration::class, 'moderator_id');
    }
    
    /**
     * Get all media files uploaded by this user
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }
}
