<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'location',
        'joined_date',
        'bio',
        'x_link',
        'instagram_link',
        'facebook_link',
        'linkedin_link',
        'blog_count',
        'travsnap_count',
        'total_likes',
        'total_views',
        'mobile_number',
        'profile_image_path'
    ];

    /**
     * Get the user that owns the details.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
