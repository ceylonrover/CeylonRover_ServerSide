<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogHighlight extends Model
{
    protected $fillable = ['blog_id', 'category', 'is_active'];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
