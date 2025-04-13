<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'content', 'author', 
        'categories', 'location', 'image', 'gallery', 'status'
    ];

    protected $casts = [
        'categories' => 'array',
        'location' => 'array',
        'gallery' => 'array',
    ];
}
