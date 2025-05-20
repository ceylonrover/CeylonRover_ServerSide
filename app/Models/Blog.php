<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'additionalinfo', 'content', 'user_id',
        'categories', 'location', 'image', 'gallery', 'review', 'status',
        'operating_hours', 'entry_fee', 'suitable_for', 'specialty',
        'closed_dates', 'route_details', 'safety_measures', 'restrictions',
        'climate', 'travel_advice', 'emergency_contacts', 'assistance',
        'type', 'views'
    ];

    protected $casts = [
        'categories' => 'array',
        'location' => 'array',
        'gallery' => 'array',
        'suitable_for' => 'array',
    ];
    
    public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'blog_user_bookmarks')->withTimestamps();
    }
}
