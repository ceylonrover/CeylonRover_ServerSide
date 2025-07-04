<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active', 'role', 'profile_image', 'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];    protected $casts = [
        'is_active' => 'boolean',
    ];public function bookmarks()
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
    
    /**
     * Get all moderator assignments for this admin
     */
    public function moderatorAssignments()
    {
        return $this->hasMany(ModeratorAssignment::class, 'moderator_id');
    }
    
    /**
     * Check if the user is an admin (admin or superAdmin)
     *
     * @return bool
     */
    public function isAdmin()
    {
        \Log::info('admin role check', ['role' => $this->role]);
        return $this->role === 'admin' || $this->role === 'superAdmin';
    }
    
    /**
     * Check if the user is a super admin
     *
     * @return bool
     */    public function isSuperAdmin()
    {
        \Log::info('admin role check', ['role' => $this->role]);
        return $this->role === 'superAdmin';
    }

    /**
     * Get the user's profile details.
     */
    public function detail()
    {
        return $this->hasOne(UserDetail::class);
    }
}
