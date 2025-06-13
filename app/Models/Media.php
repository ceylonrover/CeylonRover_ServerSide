<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'blog_id',
        'travsnap_id',
        'moderation_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'width',
        'height',
        'status',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blog that owns the media.
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Get the travsnap that owns the media.
     */
    public function travsnap(): BelongsTo
    {
        return $this->belongsTo(Travsnap::class);
    }

    /**
     * Get the URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
