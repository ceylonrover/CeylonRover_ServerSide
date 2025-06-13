<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Travsnap extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'moderation_id',
        'title',
        'description',
        'location',
        'status',
        'gallery',
        'moderator_notes',
        'is_featured',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location' => 'array',
        'gallery' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the travsnap.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderation records for this travsnap.
     */
    public function moderations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TravsnapModeration::class);
    }

    /**
     * Get the current active moderation for this travsnap.
     */
    public function activeModeration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TravsnapModeration::class)->where('is_active', true);
    }

    /**
     * Get the media files associated with this travsnap.
     */
    public function media(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Media::class);
    }
}
