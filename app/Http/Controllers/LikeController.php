<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;

class LikeController extends Controller
{
    public function toggle(Request $request, $blogId)
    {
        $user = $request->user();
        
        // Check if the user has already liked this blog
        if ($user->likedBlogs()->where('blog_id', $blogId)->exists()) {
            // If already liked, leave it alone and return a message
            return response()->json(['message' => 'Blog already liked']);
        }
        
        // If not liked yet, add the like
        $user->likedBlogs()->attach($blogId);
        
        return response()->json(['message' => 'Blog liked successfully']);
    }

    public function getLikes($blogId)
    {
        $count = Blog::findOrFail($blogId)->likedBy()->count();
        return response()->json(['likes' => $count]);
    }
}
