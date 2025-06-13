<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravsnapModeration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'travsnap_id',
        'moderator_id',
        'status',
        'rejectionReason',
        'moderator_notes',
        'published_at',
        'rejected_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the travsnap associated with the moderation.
     */
    public function travsnap(): BelongsTo
    {
        return $this->belongsTo(Travsnap::class);
    }

    /**
     * Get the moderator (user) associated with the moderation.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
