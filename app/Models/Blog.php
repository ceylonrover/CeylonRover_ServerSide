<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;    protected $fillable = [
        'title', 'slug', 'description', 'additionalinfo', 'content', 'user_id',
        'categories', 'location', 'image', 'gallery', 'review', 'status', 'is_active',
        'operatingHours', 'entryFee', 'suitableFor', 'specialty',
        'closedDates', 'routeDetails', 'safetyMeasures', 'restrictions',
        'climate', 'travelAdvice', 'emergencyContacts', 'assistance',
        'type', 'views'
    ];protected $casts = [
        'categories' => 'array',
        'location' => 'array',
        'gallery' => 'array',
        'suitableFor' => 'array',
    ];
      public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'blog_user_bookmarks')->withTimestamps();
    }
    
    /**
     * Get all moderation records for this blog
     */
    public function moderations()
    {
        return $this->hasMany(BlogModeration::class);
    }
    
    /**
     * Get the latest moderation record for this blog
     */
    public function latestModeration()
    {
        return $this->hasOne(BlogModeration::class)->latest();
    }
    
    /**
     * Get the media files associated with this blog.
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }
}
