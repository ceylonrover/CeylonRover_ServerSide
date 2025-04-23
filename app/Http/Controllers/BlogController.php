<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    public function store(Request $request)
    {
        Log::info('BlogController@store method called', ['ip' => $request->ip()]);
        
        try {
           
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'additionalinfo' => 'nullable|string',
                'content' => 'required|string',
                'author' => 'required|string',
                'categories' => 'required|array|min:1',
                'location' => 'nullable|array',
                'image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'review' => 'nullable|string', 
                'status' => 'required|in:draft,published',
            ]);
    
            
            $blog = Blog::create([
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']),
                'description' => $validated['description'],
                'additionalinfo' => $validated['additionalinfo'] ?? '', // Provide a default value
                'content' => $validated['content'],
                'author' => $validated['author'],
                'categories' => json_encode($validated['categories']),
                'location' => json_encode($validated['location'] ?? []),
                'image' => $validated['image'] ?? null,
                'gallery' => json_encode($validated['gallery'] ?? []),
                'review' => $validated['review'] ?? '', // Provide a default value
                'status' => $validated['status'],
            ]);
    
            
            return response()->json(['message' => 'Blog created successfully', 'blog' => $blog], 201);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllPosts()
    {
        Log::info('BlogController@getAllPosts method called');
        
        try {
            
            $blogs = Blog::all();

            return response()->json(['message' => 'Blogs retrieved successfully', 'blogs' => $blogs], 200);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@getAllPosts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('BlogController@update method called', ['id' => $id, 'ip' => $request->ip()]);
        
        try {
            // Validate the request data
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255', 
                'description' => 'sometimes|string',
                'additionalinfo' => 'nullable|string',
                'content' => 'sometimes|string',
                'author' => 'sometimes|string',
                'categories' => 'sometimes|array|min:1',
                'location' => 'nullable|array',
                'image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'review' => 'nullable|string',
                'status' => 'sometimes|in:draft,published',
            ]);

            // Find the blog post by ID
            $blog = Blog::findOrFail($id);

            // Update the blog post with the validated data
            $blog->update([
                'title' => $validated['title'] ?? $blog->title,
                'slug' => isset($validated['title']) ? Str::slug($validated['title']) : $blog->slug,
                'description' => $validated['description'] ?? $blog->description,
                'additionalinfo' => $validated['additionalinfo'] ?? $blog->additionalinfo,
                'content' => $validated['content'] ?? $blog->content,
                'author' => $validated['author'] ?? $blog->author,
                'categories' => isset($validated['categories']) ? json_encode($validated['categories']) : $blog->categories,
                'location' => isset($validated['location']) ? json_encode($validated['location']) : $blog->location,
                'image' => $validated['image'] ?? $blog->image,
                'gallery' => isset($validated['gallery']) ? json_encode($validated['gallery']) : $blog->gallery,
                'review' => $validated['review'] ?? $blog->review,
                'status' => $validated['status'] ?? $blog->status,
            ]);

            // Return a success response
            return response()->json(['message' => 'Blog updated successfully', 'blog' => $blog], 200);
        } catch (\Exception $e) {
            
            Log::error('Error in BlogController@update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function filter(Request $request)
    {
        $query = \App\Models\Blog::query();

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('author')) {
            $query->where('author', 'like', '%' . $request->author . '%');
        }

        if ($request->has('category')) {
            $query->whereJsonContains('categories', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->get());
    }

    public function search(Request $request)
    {
        $search = $request->input('q');

        $blogs = \App\Models\Blog::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('content', 'like', "%$search%")
                    ->orWhere('author', 'like', "%$search%")
                    ->orWhereJsonContains('categories', $search);
                });
            })
            ->latest()
            ->get();

        return response()->json($blogs);
    }


}
