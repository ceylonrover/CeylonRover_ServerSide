<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, $blogId)
    {
        $user = $request->user();
        $liked = $user->likedBlogs()->toggle($blogId); // Laravel handles attach/detach

        $action = count($liked['attached']) > 0 ? 'liked' : 'unliked';

        return response()->json(['message' => "Blog {$action} successfully"]);
    }

    public function getLikes($blogId)
    {
        $count = Blog::findOrFail($blogId)->likedBy()->count();
        return response()->json(['likes' => $count]);
    }
}
