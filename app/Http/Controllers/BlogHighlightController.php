<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogHighlightController extends Controller
{
    // Create or Update Highlight
    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'blog_id' => 'required|exists:blogs,id',
            'category' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_blog' => 'boolean'
        ]);

        $highlight = BlogHighlight::updateOrCreate(
            ['blog_id' => $validated['blog_id'], 'category' => $validated['category']],
            ['is_active' => $validated['is_active'] ?? true]
        );

        return response()->json(['message' => 'Highlight saved', 'highlight' => $highlight]);
    }

    // List Highlights
    public function index()
    {
        return BlogHighlight::with('blog')->get();
    }

    // Delete Highlight
    public function destroy($id)
    {
        $highlight = BlogHighlight::findOrFail($id);
        $highlight->delete();

        return response()->json(['message' => 'Highlight removed']);
    }
}
