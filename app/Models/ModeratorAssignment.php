<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModeratorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderator_id',
        'content_id',
        'content_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the moderator (admin user)
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the moderated content (blog or travsnap)
     */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }
}
