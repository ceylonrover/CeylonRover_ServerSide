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
        $bookmarked = $user->bookmarkedBlogs()->toggle($blogId);

        $action = count($bookmarked['attached']) > 0 ? 'bookmarked' : 'unbookmarked';

        return response()->json(['message' => "Blog {$action} successfully"]);
    }
    // 📚 Get all bookmarked blogs
    public function getBookmarks($blogId)
    {
        $count = Blog::findOrFail($blogId)->bookmarkedBy()->count();
        return response()->json(['bookmarks' => $count]);
    }

    public function userBookmarks(Request $request)
    {
        $bookmarks = $request->user()->bookmarkedBlogs()->get();
        return response()->json(['bookmarked_blogs' => $bookmarks]);
    }
}
