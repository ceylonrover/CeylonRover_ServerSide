<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;

class BookmarkController extends Controller
{
    // 🔖 Toggle bookmark (add/remove)
    public function toggle(Request $request, $blogId)
    {
        $user = $request->user();
        $blog = Blog::findOrFail($blogId);

        if ($user->bookmarks()->where('blog_id', $blogId)->exists()) {
            $user->bookmarks()->detach($blogId);
            return response()->json(['message' => 'Bookmark removed']);
        } else {
            $user->bookmarks()->attach($blogId);
            return response()->json(['message' => 'Bookmark added']);
        }
    }

    // 📚 Get all bookmarked blogs
    public function index(Request $request)
    {
        $user = $request->user();
        $bookmarks = $user->bookmarks()->latest()->get();

        return response()->json($bookmarks);
    }
}
